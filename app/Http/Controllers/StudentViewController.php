<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Client\ConnectionException;

class StudentViewController extends Controller
{
    public function showDashboard(Request $request)
    {
        $studentId = trim($request->query('id', ''));
        $isImpersonation = false;
        $impersonatedBy = null;

        // If no id provided, try to use the authenticated user's student identifier
        if ($studentId === '' && Auth::check()) {
            $user = Auth::user();
            // Prefer a 'student_id' attribute if present, fall back to the user's id
            $studentId = trim($user->student_id ?? $user->id ?? '');
            $isImpersonation = true;
        }

        // Admin impersonation: if an admin provides an id that's not their own
        if (Auth::check() && ($request->query('id', '') !== '')) {
            $user = Auth::user();
            $ownId = trim($user->student_id ?? $user->id ?? '');
            if (! empty($user->is_admin) && $request->query('id') !== $ownId) {
                $isImpersonation = true;
                $impersonatedBy = $user->name ?? null;
            }
        }
        $financeData = null;
        $errorMessage = null;
        
        $response = null;

        if ($studentId !== '') {
            try {
                $response = Http::timeout(10)->get('http://196.220.119.61:8080/testfinancials.php', [
                    'id' => $studentId,
                ]);
            } catch (ConnectionException $exception) {
                $errorMessage = 'Unable to reach financial service. Please check your network or try again later.';
            }

            if (! $errorMessage) {
                if ($response && $response->successful()) {
                    $body = $response->body();
                    $jsonBody = $this->extractJsonFromBody($body);

                    if (is_array($jsonBody)) {
                        if (isset($jsonBody['data']['transactions']['account_summary'])) {
                            $financeData = $jsonBody['data']['transactions'];
                        } elseif (isset($jsonBody['transactions']['account_summary'])) {
                            $financeData = $jsonBody['transactions'];
                        } elseif (isset($jsonBody['account_summary'])) {
                            $financeData = $jsonBody;
                        } else {
                            $errorMessage = 'The finance service returned an unexpected response structure.';
                        }

                        if ($financeData && isset($financeData['statement_records']) && is_array($financeData['statement_records'])) {
                            // Normalize keys to lower-case and compute USD totals
                            $totalBilledUsd = 0.0;
                            $totalPaidUsd = 0.0;

                            $normalized = array_map(function ($record) use (&$totalBilledUsd, &$totalPaidUsd) {
                                if (! is_array($record)) {
                                    return $record;
                                }

                                $r = array_change_key_case($record, CASE_LOWER);

                                $ex = isset($r['exchangerate']) && floatval($r['exchangerate']) > 0 ? floatval($r['exchangerate']) : null;

                                // USD debit: prefer foreigndebit, fallback to debit/exchangerate
                                if (! empty($r['foreigndebit']) && floatval($r['foreigndebit']) > 0) {
                                    $totalBilledUsd += floatval($r['foreigndebit']);
                                } elseif (! empty($r['debit']) && floatval($r['debit']) > 0 && $ex) {
                                    $totalBilledUsd += floatval($r['debit']) / $ex;
                                }

                                // USD credit: prefer absolute(foreigncredit), fallback to abs(credit)/exchangerate
                                if (isset($r['foreigncredit']) && floatval($r['foreigncredit']) < 0) {
                                    $totalPaidUsd += abs(floatval($r['foreigncredit']));
                                } elseif (isset($r['credit']) && floatval($r['credit']) < 0 && $ex) {
                                    $totalPaidUsd += abs(floatval($r['credit'])) / $ex;
                                }

                                return $r;
                            }, $financeData['statement_records']);

                            $financeData['statement_records'] = $normalized;

                            // Ensure account_summary exists and set totals in USD
                            if (! isset($financeData['account_summary']) || ! is_array($financeData['account_summary'])) {
                                $financeData['account_summary'] = [];
                            }

                            // Override totals with USD-calculated values
                            $financeData['account_summary']['total_billed'] = $totalBilledUsd;
                            $financeData['account_summary']['total_paid'] = $totalPaidUsd;
                        }
                    } else {
                        $errorMessage = 'The finance service returned an unexpected response format.';
                    }
                } elseif ($response) {
                    $errorMessage = 'The finance service returned status ' . $response->status() . '. Please try again.';
                } else {
                    $errorMessage = 'The finance service could not be called. Please try again.';
                }
            }
        }

        return view('Finance.dashboard', [
            'financeData' => $financeData,
            'studentId' => $studentId,
            'errorMessage' => $errorMessage,
            'isImpersonation' => $isImpersonation,
            'impersonatedBy' => $impersonatedBy,
        ]);
    }

    private function extractJsonFromBody(string $body): ?array
    {
        // Remove any leading HTML or PHP warning noise and capture the JSON object.
        $jsonStart = strpos($body, '{');
        if ($jsonStart === false) {
            return null;
        }

        $jsonCandidate = substr($body, $jsonStart);
        $decoded = json_decode($jsonCandidate, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }
}
