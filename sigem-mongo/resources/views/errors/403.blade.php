@extends('errors.layout')

@section('title', 'Acceso denegado')
@section('code', '403')
@section('message', 'Acceso denegado')
@section('submessage', 'No tienes permiso para acceder a esta sección.')

@section('action')
    @php
        $redirectUrl = '/admin/login';
        if (auth()->check()) {
            $user = auth()->user();
            $isAdmin = method_exists($user, 'hasRole')
                ? ($user->hasRole(['Administrador', 'admin']) || ($user->tipo_usuario ?? null) === 'Administrador')
                : (($user->tipo_usuario ?? null) === 'Administrador');

            $redirectUrl = $isAdmin ? '/admin' : '/servicio-social';
        }
    @endphp
    <a href="{{ url($redirectUrl) }}" class="btn">
        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        Ir a mi panel
    </a>
@endsection
