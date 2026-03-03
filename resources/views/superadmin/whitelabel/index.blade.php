@extends('layouts.master')

@section('title', 'Whitelabel Settings')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <h3 class="page-header"><i class="fa fa-cogs"></i> Whitelabel Configuration</h3>
    </div>
</div>

<div class="row">
    @foreach($tenants as $tenant)
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h2><i class="fa fa-globe"></i> {{ $tenant->domain }}</h2>
            </div>
            <div class="panel-body">
                <form action="{{ route('superadmin.whitelabel.update', $tenant->id) }}" method="POST" class="form-horizontal">
                    @csrf
                    @method('PUT')
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Instance Name</label>
                        <div class="col-sm-9">
                            <input type="text" name="name" class="form-control" value="{{ $tenant->name }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Primary Color</label>
                        <div class="col-sm-9">
                            <input type="color" name="primary_color" class="form-control" value="{{ $tenant->primary_color }}" style="height: 40px;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Logo URL</label>
                        <div class="col-sm-9">
                            <input type="text" name="logo_url" class="form-control" value="{{ $tenant->logo_url }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Footer Content</label>
                        <div class="col-sm-9">
                            <textarea name="footer_content" class="form-control" rows="3">{{ $tenant->footer_content }}</textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-3 col-lg-9">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
