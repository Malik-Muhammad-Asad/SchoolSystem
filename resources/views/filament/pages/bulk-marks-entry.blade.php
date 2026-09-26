<x-filament::page>

    {{-- ── Filter Form ─────────────────────────────────────────── --}}
    <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow mb-6">
        {{ $this->form }}

        <div class="mt-4 flex gap-3">
            <x-filament::button wire:click="loadGrid" color="primary" icon="heroicon-o-magnifying-glass">
                Search
            </x-filament::button>

            @if ($isLoaded)
                <x-filament::button wire:click="saveMarks" color="success" icon="heroicon-o-check">
                    Save All Marks
                </x-filament::button>
            @endif
        </div>
    </div>

    {{-- ── Grid ───────────────────────────────────────────────── --}}
    @if ($isLoaded && count($students) > 0 && count($columns) > 0)

        {{-- Group columns by exam for the spanning header --}}
        @php
            // Build exam groups: [ exam_id => ['exam_name'=>, 'term_name'=>, 'subject_count'=>, cols=>[]] ]
            $examGroups = [];
            foreach ($columns as $col) {
                $eid = $col['exam_id'];
                if (!isset($examGroups[$eid])) {
                    $examGroups[$eid] = [
                        'exam_name'     => $col['exam_name'],
                        'term_name'     => $col['term_name'],
                        'subject_count' => 0,
                        'cols'          => [],
                    ];
                }
                $examGroups[$eid]['subject_count']++;
                $examGroups[$eid]['cols'][] = $col;
            }
        @endphp

        <div class="overflow-x-auto rounded-xl shadow">
            <table class="min-w-max w-full border-collapse text-sm">

                {{-- ── Header Row 1: Exam groups ── --}}
                <thead class="bg-primary-600 text-white">
                    <tr>
                        <th rowspan="2" class="px-3 py-2 border border-primary-700 text-left w-8">#</th>
                        <th rowspan="2" class="px-3 py-2 border border-primary-700 text-left min-w-[140px]">Student Name</th>
                        <th rowspan="2" class="px-3 py-2 border border-primary-700 text-left min-w-[130px]">Father Name</th>

                        @foreach ($examGroups as $examId => $group)
                            <th colspan="{{ $group['subject_count'] }}"
                                class="px-3 py-2 border border-primary-700 text-center font-semibold">
                                {{ $group['term_name'] }} — {{ $group['exam_name'] }}
                            </th>
                        @endforeach
                    </tr>

                    {{-- ── Header Row 2: Subject names + editable max marks ── --}}
                    <tr class="bg-primary-700">
                        @foreach ($columns as $col)
                            <th class="px-2 py-1 border border-primary-800 text-center min-w-[120px]">
                                <div class="text-xs font-semibold mb-1">{{ $col['subject_name'] }}</div>
                                <div class="flex items-center gap-1 justify-center">
                                    <span class="text-xs opacity-75">Max:</span>
                                    <input style="color: black;"
                                        type="number"
                                        min="0"
                                        wire:model.lazy="subjectNumbers.{{ $col['col_key'] }}"
                                        class="w-16 text-center text-xs text-gray-900 bg-white border border-primary-400 rounded px-1 py-0.5 focus:outline-none focus:ring-1 focus:ring-white"
                                    />
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>

                {{-- ── Body: one row per student ── --}}
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($students as $index => $student)
                        <tr class="{{ $index % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50 dark:bg-gray-800' }} hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors">

                            <td class="px-3 py-2 border border-gray-200 dark:border-gray-700 text-center text-gray-500">
                                {{ $index + 1 }}
                            </td>
                            <td class="px-3 py-2 border border-gray-200 dark:border-gray-700 font-medium text-gray-800 dark:text-gray-200">
                                {{ $student['name'] }}
                            </td>
                            <td class="px-3 py-2 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                                {{ $student['father_name'] }}
                            </td>

                            @foreach ($columns as $col)
                                @php
                                    $cellKey = "{$student['id']}_{$col['exam_id']}_{$col['subject_id']}";
                                    $maxKey  = $col['col_key'];
                                    $max     = $subjectNumbers[$maxKey] ?? 100;
                                    $val     = $gridData[$cellKey] ?? '0';
                                    $isOver  = (float)$val > (float)$max && (float)$max > 0;
                                @endphp
                                <td class="px-2 py-1 border border-gray-200 dark:border-gray-700 text-center">
                                    <input
                                        type="number"
                                        min="0"
                                        max="{{ $max }}"
                                        wire:model.lazy="gridData.{{ $cellKey }}"
                                        class="w-full text-center text-sm rounded px-1 py-1 border transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500
                                               {{ $isOver
                                                    ? 'border-red-400 bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300 focus:ring-red-400'
                                                    : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100' }}"
                                    />
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>

        {{-- ── Bottom Save button ── --}}
        <div class="mt-4">
            <x-filament::button wire:click="saveMarks" color="success" icon="heroicon-o-check" size="lg">
                Save All Marks
            </x-filament::button>
        </div>

    @elseif ($isLoaded)
        <div class="p-6 text-center text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 rounded-xl shadow">
            No data available. Please check that the selected class has active students, assigned subjects, and that the selected terms have exams.
        </div>
    @else
        <div class="p-6 text-center text-gray-400 dark:text-gray-500 bg-white dark:bg-gray-800 rounded-xl shadow border-2 border-dashed border-gray-200 dark:border-gray-700">
            <x-heroicon-o-table-cells class="mx-auto w-12 h-12 mb-3 text-gray-300 dark:text-gray-600" />
            <p class="text-sm">Select a <strong>Class</strong> and one or more <strong>Terms</strong>, then click <strong>Search</strong> to load the marks grid.</p>
        </div>
    @endif

</x-filament::page>
