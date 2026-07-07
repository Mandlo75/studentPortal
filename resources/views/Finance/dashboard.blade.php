<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduRole Student Wallet</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen text-gray-800">

    <div class="container mx-auto p-6 max-w-5xl">
        <header class="mb-8">
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Finance Portal</h1>
            <p class="text-slate-500 mt-1">Live statement summary synced directly with Sage ERP</p>
        </header>

        <div class="mb-8">
            <form method="get" action="{{ route('student.dashboard') }}" class="grid gap-4 sm:grid-cols-[1fr_auto] items-end">
                <div>
                    <label for="student-id" class="block text-sm font-semibold text-slate-700 mb-2">Student ID</label>
                    <input id="student-id" name="id" type="text" value="{{ old('id', $studentId) }}" placeholder="Enter student ID"
                        class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-100" />
                </div>

                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-sky-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-sky-200 transition hover:bg-sky-700">
                    Load Financial Summary
                </button>
            </form>
            </form>

            @if(!empty($impersonatedBy))
                <div class="mt-3 text-sm text-amber-700">
                    Impersonating <span class="font-semibold text-slate-900">{{ $studentId }}</span> (as {{ $impersonatedBy }}).
                    <a href="{{ route('student.dashboard') }}" class="ml-3 text-xs underline">Stop impersonation</a>
                </div>
            @else
                @if($studentId !== '')
                    <p class="mt-3 text-sm text-slate-500">
                        Showing results for <span class="font-semibold text-slate-900">{{ $studentId }}</span>.
                        @if(!empty($isImpersonation))
                            <span class="ml-2 text-xs text-slate-400">(loaded from your account)</span>
                        @endif
                    </p>
                @else
                    <p class="mt-3 text-sm text-slate-500">Enter a student ID above to retrieve their finance dashboard.</p>
                @endif
            @endif

            @if(Auth::check() && (!empty(Auth::user()->is_admin)))
                <div class="mt-3 text-sm text-slate-500">
                    <span class="font-semibold">Admin tools:</span> enter a Student ID above to impersonate any student.
                </div>
            @endif
        </div>

        @if($errorMessage)
            <div class="bg-rose-50 border-l-4 border-rose-500 text-rose-700 p-4 rounded mb-6" role="alert">
                <p class="font-bold">Unable to load financial data</p>
                <p class="text-sm">{{ $errorMessage }}</p>
            </div>
        @endif

        @if($studentId !== '' && $financeData)
            @php
                $summary = $financeData['account_summary'];
                $status = $summary['interpretation'];
            @endphp

            <div class="mb-8 p-6 rounded-2xl border-l-[10px] shadow-sm transition-all duration-300
                {{ $status === 'OWING' ? 'bg-red-50/60 border-red-500 text-red-950' : '' }}
                {{ $status === 'CREDIT' ? 'bg-emerald-50/60 border-emerald-500 text-emerald-950' : '' }}
                {{ $status === 'CLEAR' ? 'bg-sky-50/60 border-sky-500 text-sky-950' : '' }}">
                
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <span class="text-xs uppercase font-bold tracking-widest opacity-60">Calculated Ledger Balance</span>
                        <h2 class="text-4xl font-black tracking-tight mt-1">
                            ${{ number_format(abs($summary['balance']), 2) }}
                            <span class="text-lg font-semibold tracking-normal opacity-85">
                                {{ $status === 'CREDIT' ? 'In Credit 🟢' : ($status === 'OWING' ? 'Outstanding Debt 🔴' : 'Fully Settled 🔵') }}
                            </span>
                        </h2>
                        <p class="text-sm mt-2 font-medium opacity-80">{{ $summary['message'] ?? 'Summary information is available.' }}</p>
                    </div>

                    <div>
                        @if($status === 'OWING')
                            <button class="bg-red-600 hover:bg-red-700 text-white text-sm font-bold py-3 px-6 rounded-xl shadow-md shadow-red-200 transition">
                                Clear Outstanding Balance
                            </button>
                        @else
                            <div class="bg-emerald-600 text-white text-xs font-bold px-4 py-2 rounded-full tracking-wider uppercase">
                                ✓ Account Cleared
                            </div>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mt-6 pt-5 border-t border-slate-200 text-sm">
                    <div>
                        <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wide">Total Billed Invoices (Debits)</span>
                        <span class="text-lg font-bold text-slate-700">${{ number_format($summary['total_billed'] ?? 0, 2) }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wide">Total Logged Receipts (Credits)</span>
                        <span class="text-lg font-bold text-emerald-700">${{ number_format($summary['total_paid'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100">
                    <h3 class="font-bold text-slate-700">Detailed Statement Ledger</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70 text-slate-500 font-bold uppercase tracking-wider text-xs">
                            <tr>
                                <th class="px-6 py-3.5 text-left">Date</th>
                                <th class="px-6 py-3.5 text-left">Reference</th>
                                <th class="px-6 py-3.5 text-left">Description</th>
                                <th class="px-6 py-3.5 text-right">Debit (USD / ZIG)</th>
                                <th class="px-6 py-3.5 text-right">Credit (USD / ZIG)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($financeData['statement_records'] ?? [] as $record)
                                <tr class="hover:bg-slate-50/50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-500">{{ $record['date'] ?? '—' }}</td>
                                    <td class="px-6 py-4 font-mono text-xs font-bold text-slate-400">{{ $record['reference'] ?? '—' }}</td>
                                    <td class="px-6 py-4 font-medium text-slate-800">{{ $record['description'] ?? '—' }}</td>

                                    <td class="px-6 py-4 text-right">
                                        <div class="text-slate-900 font-semibold">
                                            @if(!empty($record['foreigndebit']) && $record['foreigndebit'] > 0)
                                                USD {{ number_format($record['foreigndebit'], 2) }}
                                            @elseif(!empty($record['debit']) && $record['debit'] > 0)
                                                USD {{ number_format(($record['debit'] / ($record['exchangerate'] ?? 1)), 2) }}
                                            @else
                                                USD —
                                            @endif
                                        </div>
                                        <div class="text-xs text-slate-500 mt-1">
                                            @if(!empty($record['debit']) && $record['debit'] > 0)
                                                ZIG {{ number_format($record['debit'], 2) }}
                                            @else
                                                ZIG —
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-right">
                                        <div class="text-slate-900 font-semibold">
                                            @if(!empty($record['foreigncredit']) && $record['foreigncredit'] < 0)
                                                USD {{ number_format(abs($record['foreigncredit']), 2) }}
                                            @elseif(!empty($record['credit']) && $record['credit'] < 0)
                                                USD {{ number_format((abs($record['credit']) / ($record['exchangerate'] ?? 1)), 2) }}
                                            @else
                                                USD —
                                            @endif
                                        </div>
                                        <div class="text-xs text-slate-500 mt-1">
                                            @if(!empty($record['credit']) && $record['credit'] < 0)
                                                ZIG {{ number_format(abs($record['credit']), 2) }}
                                            @else
                                                ZIG —
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-slate-400 italic">
                                        No financial history records recorded on this account index.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($studentId !== '' && ! $errorMessage)
            <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-700 p-4 rounded" role="alert">
                <p class="font-bold">No data available</p>
                <p class="text-sm">We could not find finance data for <strong>{{ $studentId }}</strong>. Try another student ID.</p>
            </div>
        @endif
    </div>

</body>
</html>