<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Générer des questions via l'IA</x-slot>
        <x-slot name="description">
            Claude génère automatiquement des QCM de qualité pédagogique à partir d'un thème.
        </x-slot>

        <form wire:submit="generate">
            {{ $this->form }}

            <div class="mt-6">
                <x-filament::button type="submit" icon="heroicon-o-sparkles">
                    Lancer la génération
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
