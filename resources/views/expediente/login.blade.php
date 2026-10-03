@extends('expediente.layout')
@section('title', 'Acceso de médicos')
@section('body-class', 'portal-login-body')
@section('content')
    <section class="portal-login-card portal-card">
        <div class="portal-login-symbol"><x-heroicon-o-folder-open class="portal-icon" /></div>
        <p class="portal-eyebrow">Portal de médicos</p>
        <h1>Consulta de expedientes</h1>
        <p class="portal-muted">Accede a los antecedentes de laboratorio y resultados de tus pacientes.</p>
        <form method="post" action="{{ route('expediente.login.store') }}" class="portal-login-form">
            @csrf
            <label class="portal-field">Usuario
                <input name="usuario" value="{{ old('usuario') }}" placeholder="MED-12" autocomplete="username" autocapitalize="characters" required maxlength="32" autofocus>
            </label>
            <label class="portal-field">Contraseña
                <input name="password" type="password" autocomplete="current-password" required maxlength="128">
            </label>
            <button class="portal-button portal-button-primary" type="submit">Entrar al expediente<x-heroicon-o-arrow-right class="portal-icon" /></button>
        </form>
        <p class="portal-login-help">Solicita tu usuario y contraseña al laboratorio.</p>
        <a class="portal-contact" href="{{ config('laboratorio.telefono_uri') }}"><x-heroicon-o-phone class="portal-icon" />{{ config('laboratorio.telefono') }}</a>
    </section>
@endsection
