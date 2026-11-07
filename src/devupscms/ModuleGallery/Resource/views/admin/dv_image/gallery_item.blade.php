<div id="image-{{$dv_image->getId()}}"
     class="col-md-3 col-xl-2 image-item ">
    <div class="card mb-3  ">
        <div class="card-header">
            @if($dv_image->folder->getId())
                {{$dv_image->folder->name['fr']}}/
            @endif
            <button onclick="model.dvimage._delete(this, {{$dv_image->getId()}}, this)"
                    class="btn btn-danger">delete
            </button>
            <button onclick="model.dvimage._edit({{$dv_image->getId()}})"
                    class="btn btn-info">edit
            </button>
        </div>
        <div class="">
            <img src="{{$dv_image->srcImage('150_')}}"/>
        </div>
        <div class="card-footer">
            {{$dv_image->getId()}} - {{$dv_image->getImage()}}

            <div class="widget-numbers text-success">
            </div>
        </div>
    </div>
</div>
