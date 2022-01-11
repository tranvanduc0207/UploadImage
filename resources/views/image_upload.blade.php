@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <form action="{{route('upload')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="card">
                        <div class="card-header">
                            <h1>Upload image</h1>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <label for="">Choose an image</label>
                                    <input type="hidden" name="author_code" value="{{$author_code}}">
                                    <input type="file" name="images" class="form-control" placeholder="Image link">
                                    <button class="btn btn-primary">Upload</button>
                                </div>    
                            </div>
                                         
                        </div>
                    </div>
                    
                </div>
            </form>
        </div>
    </div>
@endsection