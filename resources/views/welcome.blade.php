@extends('layouts.master')

@section('title', 'Welcome')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h2><i class="fa fa-map-marker red"></i><strong>Welcome to PrivTuition</strong></h2>
                <div class="panel-actions">
                    <a href="index.html#" class="btn-setting"><i class="fa fa-rotate-right"></i></a>
                    <a href="index.html#" class="btn-minimize"><i class="fa fa-chevron-up"></i></a>
                    <a href="index.html#" class="btn-close"><i class="fa fa-times"></i></a>
                </div>
            </div>
            <div class="panel-body-map">
                <div class="text-center" style="padding: 50px;">
                    <h1>Welcome to the SIM-LP Enterprise Edition</h1>
                    <p>This application is using the "Creative Bootstrap 3 Responsive Admin Template".</p>
                    <p>
                        <a class="btn btn-primary btn-lg" href="#" role="button">Learn more</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
        <div class="info-box blue-bg">
            <i class="fa fa-cloud-download"></i>
            <div class="count">6.674</div>
            <div class="title">Download</div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
        <div class="info-box brown-bg">
            <i class="fa fa-shopping-cart"></i>
            <div class="count">7.538</div>
            <div class="title">Purchased</div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
        <div class="info-box dark-bg">
            <i class="fa fa-thumbs-o-up"></i>
            <div class="count">4.362</div>
            <div class="title">Order</div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
        <div class="info-box green-bg">
            <i class="fa fa-cubes"></i>
            <div class="count">1.426</div>
            <div class="title">Stock</div>
        </div>
    </div>
</div>
@endsection
