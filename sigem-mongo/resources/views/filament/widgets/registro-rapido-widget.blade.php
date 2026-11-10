<div class="bg-white rounded-lg shadow-lg overflow-hidden h-full flex flex-col">
    <div class="bg-gradient-to-r from-[#235B4E] via-[#1B396A] to-[#235B4E] px-6 py-5">
        <div class="flex items-center gap-3">
            <div class="bg-white/20 p-2.5 rounded-lg">
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"></path>
                </svg>
            </div>
            <h3 class="text-white text-base font-bold">
                Registro Rápido
            </h3>
        </div>
    </div>

    <form wire:submit.prevent="crear" class="p-6 space-y-5 flex-1 flex flex-col h-full">
            <div class="space-y-3 flex-1">
                {{ $this->form }}
            </div>

            <button type="submit" class="w-full text-white px-4 py-3 rounded-lg hover:shadow-lg transition-all duration-200 flex items-center justify-center font-bold text-sm uppercase tracking-wide" style="background-color: #235B4E;">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"></path>
            </svg>
            Registrar
        </button>
    </form>
</div>