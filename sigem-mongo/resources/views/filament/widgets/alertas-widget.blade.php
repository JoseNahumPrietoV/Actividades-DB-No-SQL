<div class="bg-white rounded-lg shadow-lg overflow-hidden h-full flex flex-col">
    <div class="bg-gradient-to-r from-[#9D2449] via-[#B38E5D] to-[#9D2449] px-6 py-5">
        <div class="flex items-center gap-3">
            <div class="bg-white/20 p-2.5 rounded-lg">
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"></path>
                </svg>
            </div>
            <h3 class="text-white text-base font-bold">
                Alertas del sistema
            </h3>
        </div>
    </div>

    <div class="p-6 space-y-4 flex-1 overflow-y-auto">
        {{-- Alerta de Inventario dañado --}}
        @if($inventarioDanado > 0)
            <div class="flex items-start gap-3 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg hover:bg-red-100 transition-colors">
                <div class="flex-shrink-0 pt-0.5">
                    <svg class="h-5 w-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-red-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                        Inventario dañado
                    </p>
                    <p class="text-xs text-red-700 mt-1">{{ $inventarioDanado }} activo(s) requieren atención inmediata</p>
                </div>
            </div>
        @else
            <div class="flex items-start gap-3 p-4 bg-green-50 border-l-4 border-green-500 rounded-lg">
                <div class="flex-shrink-0 pt-0.5">
                    <svg class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-green-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                        Inventario en buen estado
                    </p>
                    <p class="text-xs text-green-700 mt-1">No hay activos dañados registrados</p>
                </div>
            </div>
        @endif

        {{-- Alerta de Rentas Próximas a Vencer --}}
        @if($rentasProximas > 0)
            <div class="flex items-start gap-3 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded-lg hover:bg-yellow-100 transition-colors">
                <div class="flex-shrink-0 pt-0.5">
                    <svg class="h-5 w-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-yellow-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-yellow-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                        Rentas por Vencer
                    </p>
                    <p class="text-xs text-yellow-700 mt-1">{{ $rentasProximas }} renta(s) vencerá(n) en los próximos 7 días</p>
                </div>
            </div>
        @endif

        {{-- Alerta de Mantenimientos Críticos --}}
        @if($mantenimientosCriticos > 0)
            <div class="flex items-start gap-3 p-4 bg-orange-50 border-l-4 border-orange-500 rounded-lg hover:bg-orange-100 transition-colors">
                <div class="flex-shrink-0 pt-0.5">
                    <svg class="h-5 w-5 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 2.697m8.368 12.192a6 6 0 01-8.368-8.368m8.368 8.368l2.12 2.12a1 1 0 001.414-1.414l-2.12-2.12m0 0l2.12-2.12a1 1 0 00-1.414-1.414l-2.12 2.12m0 0a6 6 0 10-8.485 8.485m8.485-8.485L5.11 2.697" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-orange-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-orange-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 2.697m8.368 12.192a6 6 0 01-8.368-8.368m8.368 8.368l2.12 2.12a1 1 0 001.414-1.414l-2.12-2.12m0 0l2.12-2.12a1 1 0 00-1.414-1.414l-2.12 2.12m0 0a6 6 0 10-8.485 8.485m8.485-8.485L5.11 2.697" clip-rule="evenodd" /></svg>
                        Mantenimientos Pendientes
                    </p>
                    <p class="text-xs text-orange-700 mt-1">{{ $mantenimientosCriticos }} solicitud(es) requiere(n) atención</p>
                </div>
            </div>
        @endif

        {{-- Mensaje de Alerta General --}}
        @if($inventarioDanado === 0 && $rentasProximas === 0 && $mantenimientosCriticos === 0)
            <div class="flex items-start gap-3 p-4 bg-blue-50 border-l-4 border-blue-500 rounded-lg">
                <div class="flex-shrink-0 pt-0.5">
                    <svg class="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1h2v2H7V4zm2 4H7v2h2V8zm2-4h2v2h-2V4zm2 4h-2v2h2V8z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-blue-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1h2v2H7V4zm2 4H7v2h2V8zm2-4h2v2h-2V4zm2 4h-2v2h2V8z" clip-rule="evenodd" /></svg>
                        Todo está en orden
                    </p>
                    <p class="text-xs text-blue-700 mt-1">No hay alertas activas en el sistema</p>
                </div>
            </div>
        @endif
    </div>
</div>