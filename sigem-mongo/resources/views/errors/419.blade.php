@extends('errors.layout')

@section('title', 'Sesión expirada')
@section('code', '419')
@section('message', 'Sesión expirada')
@section('submessage', 'Tu sesión ha expirado por inactividad. Por favor inicia sesión de nuevo.')

@section('action')
    <a href="{{ url('/admin/login') }}" class="btn">
        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
        Iniciar sesión
    </a>
@endsection
