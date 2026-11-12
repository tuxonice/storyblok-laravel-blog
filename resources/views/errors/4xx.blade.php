@extends('layouts.app')

@section('content')
    <x-error-page :code="$error['code']" :title="$error['title']" :text="$error['text']" />
@endsection
