@extends('layout.main')
@section('content')
    @include('template.user_head')
    <style>
    input:disabled{
        background-color: rgba(225, 230, 239, 0.51)!important;
    }
    .form-group{
        margin-bottom: 0!important;
    }
    .form-control{
        margin-bottom: 10px;
    }
    </style>
    <body class="app header-fixed sidebar-fixed aside-menu-fixed aside-menu-hidden">
        @include('user.header')
        <div class="app-body">
            @include('user.sidebar')
            <main class="main">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/panel/user">Home</a></li>
                    <li class="breadcrumb-item active ">Form Builder</li>
                </ol>
                <div class="container-fluid">
                    <div class="animated fadeIn">
                        
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="card">
                                    <div class="card-header">
                                        Current Forms
                                    </div>
                                    <div class="card-block embedWrapper">
                                        <h5>Embed in your site</h5>
                                        <div class="embedd">
                                            <p>Simply copy & paste the code below into your html!</p>
                                            <div id="tabs">
                                                <ul>
                                                    <li><a href="#tabs-1">Raw</a></li>
                                                    <li><a href="#tabs-2">Iframe</a></li>
                                                    <li><a href="#tabs-3">Seamless</a></li>
                                                </ul>
                                                <div id="tabs-1">
                                                    <!-- Make sure you include the 'amp-iframe' extension JavaScript in the <head> -->
                                                    {{'<div class="formWrapper">'}}
                                                        <br>
                                                        {{$form_data->html}}
                                                        <br>
                                                    {{'</div>'}}
                                                </div>
                                                <div id="tabs-2">
                                                    {{'<div class="formWrapper">'}}
                                                        <br>
                                                        {{'<iframe src="'.asset('/embed-form/'.$form_data->unique_id).'" style="position:relative;width:1px;min-width:100%;*width:100%;" frameborder="0" scrolling="yes" seamless="seamless" height="403" width="100%"></iframe>'}}
                                                        <br>
                                                    {{'</div>'}}
                                                </div>
                                                <div id="tabs-3">
                                                    {{'<link href="'.asset('/css/tomcmsform.css').'" rel="stylesheet">'}}
                                                    <br>
                                                    {{'<div class="formWrapper">'}}
                                                        <br>
                                                        {{'<script src="http://code.jquery.com/jquery-1.7.2.min.js"></script>'}}
                                                        <br>
                                                        {{"<script>$('.formWrapper').html('<object data=\"".asset('/embed-form/'.$form_data->unique_id)."\"></object>')</script>"}}
                                                        <br>
                                                    {{'</div>'}}
                                                </div>
                                            </div>
                                        </div>


                                        <h5>Share a link</h5>
                                        <div class="social_link">
                                            <p>Use this URL to create a hyperlink to your form.</p>
                                            <div>
                                                <label>{{asset('/embed-form/'.$form_data->unique_id)}}</label>
                                                <a target="_blank" href="{{asset('/embed-form/'.$form_data->unique_id)}}" class='btn btn-success btn_table'><i class='fa fa-share'></i></a>
                                            </div>
                                        </div>


                                        <h5>Share on social media</h5>
                                        <div class="social_share">
                                            <p>Share the link to your form on these social media sites.</p>
                                            <div>
                                                <a target="_blank" href="https://www.facebook.com/sharer/sharer.php?u={{urlencode(asset('/embed-form/'.$form_data->unique_id))}}" class='btn btn-info btn_table'><i class='fa fa-facebook'></i></a>
                                                <a target="_blank" href="https://plus.google.com/share?url={{urlencode(asset('/embed-form/'.$form_data->unique_id))}}" class='btn btn-danger btn_table'><i class='fa fa-google'></i></a>
                                                <a target="_blank" href="https://twitter.com/intent/tweet?url={{urlencode(asset('/embed-form/'.$form_data->unique_id))}}" class='btn btn-info btn_table'><i class='fa fa-twitter'></i></a>

                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        
                    </div>
                </div>
            </main>
            
        </div>
        @include('template.user_scripts')
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
        <script>
            $( function(){
                $("#tabs").tabs();
            });
        </script>
        

        
    </body>
    
@endsection

