<div class="bg-white rounded-lg shadow-lg overflow-hidden h-full flex flex-col">
    <div class="bg-gradient-to-r from-[#1B396A] via-[#235B4E] to-[#1B396A] px-6 py-5">
        <div class="flex items-center gap-3">
            <div class="bg-white/20 p-2.5 rounded-lg">
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"></path>
                </svg>
            </div>
            <h3 class="text-white text-base font-bold">
                Últimas Actividades
            </h3>
        </div>
    </div>

    <div class="divide-y divide-gray-200 flex-1 overflow-y-auto">
        @forelse($actividades as $actividad)
            <div class="p-5 hover:bg-gray-50 transition-colors duration-150 border-l-4 border-transparent hover:border-[#235B4E] group">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2 flex-wrap">
                            <span class="inline-block px-3 py-1 text-xs font-bold rounded-full 
                                @switch($actividad->accion)
                                    @case('CREATE')
                                        bg-green-100 text-green-800
                                    @break
                                    @case('UPDATE')
                                        bg-blue-100 text-blue-800
                                    @break
                                    @case('DELETE')
                                        bg-red-100 text-red-800
                                    @break
                                    @default
                                        bg-gray-100 text-gray-800
                                @endswitch">
                                {{ strtoupper($actividad->accion) }}
                            </span>
                            <span class="text-xs text-gray-500 font-semibold uppercase tracking-wide">
                                {{ $actividad->tabla_afectada }}
                            </span>
                        </div>
                        
                        <p class="text-sm text-gray-900 font-semibold mb-2">
                            {{ $actividad->detalles ?? 'Registro modificado' }}
                        </p>
                        
                        <div class="flex items-center gap-2 text-xs text-gray-600">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path>
                            </svg>
                            <span class="font-medium">{{ $actividad->usuario?->name ?? 'Sistema' }}</span>
                        </div>
                    </div>
                    
                    <div class="flex-shrink-0 text-right">
                        <span class="text-xs text-gray-500 font-semibold whitespace-nowrap">
                            @if($actividad->fecha_hora)
                                <span title="{{ $actividad->fecha_hora->format('d/m/Y H:i:s') }}" class="block">
                                    {{ $actividad->fecha_hora->diffForHumans() }}
                                </span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-12 text-center flex flex-col items-center justify-center min-h-[200px]">
                <svg class="mx-auto h-16 w-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m0 0h6m0 0h6M6 12h6m0 0h6"></path>
                </svg>
                <p class="text-gray-500 text-sm font-medium">No hay actividades registradas</p>
            </div>
        @endforelse
    </div>

    @if($actividades->isNotEmpty())
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            <a href="#" class="text-xs text-[#1B396A] font-bold hover:text-[#235B4E] transition-colors uppercase tracking-wide flex items-center justify-between">
                <span>Ver historial completo</span>
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8.59 16.34l4.58-4.59-4.58-4.59L10 5.75l6 6-6 6-1.41-1.41z"></path>
                </svg>
            </a>
        </div>
    @endif
</div>