<x-filament::page>
    <!-- Render Form -->
    <div>
        {{ $this->form }}
    </div>

    <!-- Search Button -->
    <div class="mt-6">
        <x-filament::button wire:click="search" color="primary">
            Search
        </x-filament::button>
    </div>
    @if($scores && $subjects)
        <x-filament::button wire:click="downloadPDF" color="success">
            <span class="flex items-center gap-1">
                <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                Download PDF
            </span>
        </x-filament::button>
    @endif


    <!-- Results Table -->
    @if ($scores && $subjects)
        <div class="overflow-auto mt-6">
            <table class="min-w-full table-auto border border-gray-300 divide-y divide-gray-200">
                <thead class="bg-gray-100 border border-gray-300">
                    <tr>
                        <th rowspan="2"
                            class="px-4 py-2 text-left text-sm font-semibold text-gray-700 border border-gray-300">
                            Student Name
                        </th>
                        <th rowspan="2"
                            class="px-4 py-2 text-left text-sm font-semibold text-gray-700 border border-gray-300">
                            Father Name
                        </th>
                        @foreach ($subjects as $subject)
                            <th colspan="{{ count($exams) + 1 }}"
                                class="px-4 py-2 text-center text-sm font-semibold text-gray-700 border border-gray-300">
                                {{ $subject->name }}
                            </th>
                        @endforeach
                        <th rowspan="2"
                            class="px-4 py-2 text-center text-sm font-semibold text-blue-800 bg-blue-100 border border-gray-300">
                            Total
                        </th>
                        @if ($searchedPreviousTerm)
                        <th rowspan="2"
                            class="px-4 py-2 text-center text-sm font-semibold text-green-800 bg-green-100 border border-gray-300">
                            Prev Term Total
                        </th>
                        @endif
                        @if ($searchedExtraExams)
                        <th rowspan="2"
                            class="px-4 py-2 text-center text-sm font-semibold text-gray-700 border border-gray-300">
                            Extra Test
                        </th>
                        @endif
                        <th rowspan="2"
                            class="px-4 py-2 text-center text-sm font-semibold text-gray-700 border border-gray-300">
                            Obtained Grand Total
                        </th>
                        <th rowspan="2"
                            class="px-4 py-2 text-center text-sm font-semibold text-gray-700 border border-gray-300 font-bold uppercase">
                            Total Marks
                        </th>
                        <th rowspan="2"
                            class="px-4 py-2 text-center text-sm font-semibold text-gray-700 border border-gray-300">
                            Percentage
                        </th>
                        <th rowspan="2"
                            class="px-4 py-2 text-center text-sm font-semibold text-gray-700 border border-gray-300">
                            Grade
                        </th>
                    </tr>
                    <tr>
                        @foreach ($subjects as $subject)
                            @foreach ($exams as $examId)
                                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 border border-gray-300">
                                    {{ $examNames[$examId] ?? 'Unknown' }}
                                </th>
                            @endforeach
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 border border-gray-300">
                                Total
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($scores as $score)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $score['name'] }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $score['father_name'] }}</td>
                            @foreach ($subjects as $subject)
                                @foreach ($exams as $examId)
                                    <td class="px-4 py-2 text-sm text-gray-700">
                                        {{ $score[$subject->name]['exams'][$examId] ?? '-' }}
                                    </td>
                                @endforeach
                                <td class="px-4 py-2 text-sm font-bold text-gray-900">
                                    {{ $score[$subject->name]['total'] }}
                                </td>
                            @endforeach
                            {{-- Term Total as Total --}}
                            <td class="px-4 py-2 text-sm font-bold text-blue-800 bg-blue-50 border border-gray-300">
                                {{ $score['termTotal'] }}
                            </td>
                            {{-- Prev Term Total --}}
                            @if ($searchedPreviousTerm)
                            <td class="px-4 py-2 text-sm font-bold text-green-800 bg-green-50 border border-gray-300">
                                {{ $score['prevTermTotal'] }}
                            </td>
                            @endif
                            {{-- Extra Test --}}
                            @if ($searchedExtraExams)
                            <td class="px-4 py-2 text-sm font-bold text-gray-900 border border-gray-300">
                                {{ $score['ExtraObtain'] }}
                            </td>
                            @endif
                            {{-- Obtained Grand Total --}}
                            <td class="px-4 py-2 text-sm font-bold text-gray-900 border border-gray-300 bg-gray-50">
                                {{ $score['total'] }}
                            </td>
                            {{-- Total Marks (New Column) --}}
                            <td class="px-4 py-2 text-sm font-bold text-gray-900 border border-gray-300 bg-gray-50">
                                {{ $score['grandMaxTotal'] }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700 border border-gray-300">
                                {{ number_format($score['percentage'], 2) }}%
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700 border border-gray-300">
                                {{ $score['grade'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="mt-6 text-gray-500">
            No results found. Please select a class, term, and exams to search.
        </div>
    @endif
</x-filament::page>