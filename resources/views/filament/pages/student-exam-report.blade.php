<x-filament::page>
    <!-- Render Form -->
    <div>
        {{$this->form }}
    </div>

    <!-- Search Button -->
    <div class="mt-6">
        <x-filament::button wire:click="search" color="primary">
            Search
        </x-filament::button>
    </div>

    <!-- Results Table -->
    @if ($scores && $subjects)
        <div class="overflow-auto mt-6">
            <table class="min-w-full table-auto border border-gray-300 divide-y divide-gray-200">
                <thead class="bg-gray-100 border border-gray-300">
                    <tr class="border border-gray-300">
                        <!-- Main Header Row -->
                        <th rowspan="2"
                            class="px-4 py-2 text-left text-sm font-semibold text-gray-700 border border-gray-300">
                            Student Name
                        </th>
                        @foreach ($subjects as $subject)
                            <th colspan="{{ count($exams) + 1 }}"
                                class="px-4 py-2 text-center text-sm font-semibold text-gray-700 border border-gray-300">
                                {{ $subject->name }}
                            </th>
                        @endforeach
                    </tr>
                    <tr class="border border-gray-300">
                        <!-- Sub Header Row -->
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
                            @foreach ($subjects as $subject)
                                @foreach ($exams as $examId)
                                    <td class="px-4 py-2 text-sm text-gray-700">
                                        {{ $score[$subject->name]['exams'][$examId] ?? '-' }} <!-- Display the score for each exam -->
                                    </td>
                                @endforeach
                                <td class="px-4 py-2 text-sm font-bold text-gray-900">
                                    {{ $score[$subject->name]['total'] ?? '-' }} <!-- Display total score for the subject -->
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif






</x-filament::page>