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
    @if ($scores)
        <div class="overflow-auto mt-6">
            <table class="min-w-full table-auto border border-gray-300 divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Student Name</th>
                        @foreach ($subjects as $subject)
                            @foreach ($exams as $exam)
                                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">
                                    {{ $subject->name }} {{ $exam }}
                                </th>
                            @endforeach
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">
                                {{ $subject->name }} Total
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($scores as $score)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $score['name'] }}</td>
                            @foreach ($score['subjects'] as $subject)
                                @foreach ($exams as $exam)
                                    <td class="px-4 py-2 text-sm text-gray-700">
                                        {{ $subject['exams'][$exam] ?? '-' }}
                                    </td>
                                @endforeach
                                <td class="px-4 py-2 text-sm font-bold text-gray-900">
                                    {{ $subject['total'] ?? '-' }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament::page>
