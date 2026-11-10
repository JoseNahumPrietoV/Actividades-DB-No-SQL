<div class="bg-white rounded-lg shadow-lg overflow-hidden h-full flex flex-col">
    <div class="bg-gradient-to-r from-[#1B396A] via-[#235B4E] to-[#1B396A] px-6 py-5">
        <div class="flex items-center gap-3">
            <div class="bg-white/20 p-2.5 rounded-lg">
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"></path>
                </svg>
            </div>
            <h3 class="text-white text-base font-bold">
                Accesos Rápidos
            </h3>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 p-6 flex-1">
        <a href="{{ route('filament.admin.resources.inventarios.index') }}" 
           class="flex flex-col items-center justify-center p-5 rounded-lg hover:shadow-lg transition-all duration-200 group border-2 border-[#1B396A]/20 hover:border-[#1B396A] bg-white">
            <div class="p-3 rounded-lg mb-3 bg-[#1B396A]/10 group-hover:bg-[#1B396A] transition-colors">
                <svg class="w-8 h-8 text-[#1B396A] group-hover:text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </div>
            <span class="text-xs font-bold text-center leading-tight text-gray-900 group-hover:text-[#1B396A]">Inventario</span>
        </a>

        <a href="{{ route('filament.admin.resources.solicituds.index') }}" 
           class="flex flex-col items-center justify-center p-5 rounded-lg hover:shadow-lg transition-all duration-200 group border-2 border-[#235B4E]/20 hover:border-[#235B4E] bg-white">
            <div class="p-3 rounded-lg mb-3 bg-[#235B4E]/10 group-hover:bg-[#235B4E] transition-colors">
                <svg class="w-8 h-8 text-[#235B4E] group-hover:text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg>
            </div>
            <span class="text-xs font-bold text-center leading-tight text-gray-900 group-hover:text-[#235B4E]">Solicitudes</span>
        </a>

        <a href="{{ route('filament.admin.resources.mantenimientos.index') }}" 
           class="flex flex-col items-center justify-center p-5 rounded-lg hover:shadow-lg transition-all duration-200 group border-2 border-[#B38E5D]/20 hover:border-[#B38E5D] bg-white">
            <div class="p-3 rounded-lg mb-3 bg-[#B38E5D]/10 group-hover:bg-[#B38E5D] transition-colors">
                <svg class="w-8 h-8 text-[#B38E5D] group-hover:text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <span class="text-xs font-bold text-center leading-tight text-gray-900 group-hover:text-[#B38E5D]">Mantenimiento</span>
        </a>

        <a href="{{ route('filament.admin.resources.proveedors.index') }}" 
           class="flex flex-col items-center justify-center p-5 rounded-lg hover:shadow-lg transition-all duration-200 group border-2 border-[#9D2449]/20 hover:border-[#9D2449] bg-white">
            <div class="p-3 rounded-lg mb-3 bg-[#9D2449]/10 group-hover:bg-[#9D2449] transition-colors">
                <svg class="w-8 h-8 text-[#9D2449] group-hover:text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
            </div>
            <span class="text-xs font-bold text-center leading-tight text-gray-900 group-hover:text-[#9D2449]">Proveedores</span>
        </a>
    </div>
</div>