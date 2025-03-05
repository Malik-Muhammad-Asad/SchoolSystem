<x-filament::page>
    <form wire:submit.prevent="search">
        {{ $this->form }}
    </form>

    @if($this->isSearched)
        <div class="mt-4">
            {{ $this->table }}
        </div>
    @else
        <div class="p-12 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary-50 text-primary-500 mb-4">
                <x-heroicon-o-funnel class="w-8 h-8" />
            </div>
            <h2 class="text-xl font-semibold text-gray-900">No Students Displayed</h2>
            <p class="mt-2 text-gray-600">Please select a class and term above, then click Search to show students.</p>
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament::page>