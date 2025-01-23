<x-filament::page>
    <div>
        <!-- <form wire:submit.prevent="exportPDF">
            <x-filament::button type="submit" class="mb-4">
                Export PDF
            </x-filament::button>
        </form> -->

        {{$this->table}}
    </div>
</x-filament::page>

