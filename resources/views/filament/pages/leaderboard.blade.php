<x-filament-panels::page>
    @php
        $rows = $this->getLeaderboard();
        $offset = ($rows->currentPage() - 1) * $rows->perPage();
    @endphp

    @if ($rows->isEmpty())
        <x-filament::section>
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <x-filament::icon
                    icon="heroicon-o-trophy"
                    class="h-12 w-12 text-gray-400 dark:text-gray-500 mb-4"
                />
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Aucune session complétée pour le moment.
                </p>
            </div>
        </x-filament::section>
    @else
        <div class="fi-ta-content divide-y divide-gray-200 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:divide-white/10 dark:bg-gray-900 dark:ring-white/10">
            <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                <thead class="divide-y divide-gray-200 dark:divide-white/5">
                    <tr>
                        <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 w-16">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">#</span>
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Joueur</span>
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-right">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Score total</span>
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-right">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Sessions</span>
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-right">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Meilleur score</span>
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-right">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Dernière session</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                    @foreach ($rows as $row)
                        @php $rank = $offset + $loop->iteration; @endphp
                        <tr class="fi-ta-row [@media(hover:hover)]:hover:bg-gray-50 dark:[@media(hover:hover)]:hover:bg-white/5">
                            <td class="fi-ta-cell px-3 py-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                @if ($rank === 1)
                                    <x-filament::badge color="warning" size="sm">🥇 1</x-filament::badge>
                                @elseif ($rank === 2)
                                    <x-filament::badge color="gray" size="sm">🥈 2</x-filament::badge>
                                @elseif ($rank === 3)
                                    <x-filament::badge color="danger" size="sm">🥉 3</x-filament::badge>
                                @else
                                    <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">{{ $rank }}</span>
                                @endif
                            </td>
                            <td class="fi-ta-cell px-3 py-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                <span class="text-sm font-medium text-gray-950 dark:text-white">{{ $row->name }}</span>
                            </td>
                            <td class="fi-ta-cell px-3 py-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-right">
                                <x-filament::badge color="primary">
                                    {{ number_format((int) $row->total_score) }} pts
                                </x-filament::badge>
                            </td>
                            <td class="fi-ta-cell px-3 py-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-right">
                                <span class="text-sm text-gray-700 dark:text-gray-200">{{ (int) $row->sessions_count }}</span>
                            </td>
                            <td class="fi-ta-cell px-3 py-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-right">
                                <span class="text-sm text-gray-700 dark:text-gray-200">{{ number_format((int) $row->best_score) }}</span>
                            </td>
                            <td class="fi-ta-cell px-3 py-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-right">
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    @if ($row->last_session_at)
                                        {{ \Carbon\Carbon::parse($row->last_session_at)->diffForHumans() }}
                                    @else
                                        —
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($rows->hasPages())
                <div class="fi-ta-footer border-t border-gray-200 px-3 py-3.5 dark:border-white/10">
                    {{ $rows->links() }}
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>
