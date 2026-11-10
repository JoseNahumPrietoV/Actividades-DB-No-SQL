@extends('errors.layout')

@section('title', 'Error del servidor')
@section('code', '500')
@section('message', 'Error del servidor')
@section('submessage', 'Algo salió mal. Por favor intenta más tarde o contacta al administrador.')

@section('action')
    <a href="{{ url('/') }}" class="btn">
        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Volver al inicio
    </a>
@endsection
