@php
    if (!isset($field['wrapperAttributes']) || !isset($field['wrapperAttributes']['data-init-function'])){
        $field['wrapperAttributes']['data-init-function'] = 'bpFieldInitCropperImageElement';
    }

    if (!isset($field['wrapperAttributes']) || !isset($field['wrapperAttributes']['data-field-name'])) {
        $field['wrapperAttributes']['data-field-name'] = $field['name'];
    }


    if (!isset($field['wrapperAttributes']) || !isset($field['wrapperAttributes']['class'])) {
        $field['wrapperAttributes']['class'] = "form-group col-sm-12 cropperImage";
    } elseif (isset($field['wrapperAttributes']) && isset($field['wrapperAttributes']['class'])) {
        $field['wrapperAttributes']['class'] .= " cropperImage";
    }

    $prefix = isset($field['prefix']) ? $field['prefix'] : '';
    $value = old(square_brackets_to_dots($field['name'])) ?? $field['value'] ?? $field['default'] ?? '';
    $name = DB::table('files')->select('path')
                    ->where('id', '=', $value)
                    ->get();
    $filePath = Storage::disk($field['disk'])->path($name->first()->path);
    $mime = Storage::disk($field['disk'])->mimeType($name->first()->path);
    $base64 = '';
    if (file_exists($filePath)) {
        $base64 = base64_encode(file_get_contents($filePath));
    }
    if (! function_exists('maximumServerUploadSizeInBytes')) {
        function maximumServerUploadSizeInBytes() {

            $val = trim(ini_get('upload_max_filesize'));
            $last = strtolower($val[strlen($val)-1]);

            switch($last) {
                // The 'G' modifier is available since PHP 5.1.0
                case 'g':
                    $val = (int)$val * 1073741824;
                    break;
                case 'm':
                    $val = (int)$val * 1048576;
                    break;
                case 'k':
                    $val = (int)$val * 1024;
                    break;
            }

            return $val;
        }
    }

    $max_image_size_in_bytes = $field['max_file_size'] ?? (int)maximumServerUploadSizeInBytes();
@endphp

<div data-preview="#{{ $field['name'] }}"
     data-aspectRatio="{{ isset($field['aspect_ratio']) ? $field['aspect_ratio'] : 0 }}"
     data-crop="{{ isset($field['crop']) ? $field['crop'] : false }}"
    @include('crud::inc.field_wrapper_attributes')>
    <div>
        <label>{!! $field['label'] !!}</label>
        @include('crud::inc.field_translatable_icon')
    </div>
    <!-- Wrap the image or canvas element with a block element (container) -->
    <div class="row">
        <div class="col-sm-6" data-handle="previewArea" style="margin-bottom: 20px;">
            <img data-handle="mainImage" src="data:{{ $mime }};base64,{{ $base64 }}">
        </div>
        @if(isset($field['crop']) && $field['crop'])
            <div class="col-sm-3" data-handle="previewArea">
                <div class="docs-preview clearfix">
                    <div id="{{ $field['name'] }}" class="img-preview preview-lg">
                        <img src="" style="display: block; min-width: 0px !important; min-height: 0px !important; max-width: none !important; max-height: none !important; margin-left: -32.875px; margin-top: -18.4922px; transform: none;">
                    </div>
                </div>
            </div>
        @endif
    </div>
    <div class="btn-group">
        @if(isset($field['crop']) && $field['crop'])
            <button class="btn btn-light btn-sm" data-handle="rotateLeft" type="button" style="display: none;"><i class="fa fa-rotate-left"></i></button>
            <button class="btn btn-light btn-sm" data-handle="rotateRight" type="button" style="display: none;"><i class="fa fa-rotate-right"></i></button>
            <button class="btn btn-light btn-sm" data-handle="zoomIn" type="button" style="display: none;"><i class="fa fa-search-plus"></i></button>
            <button class="btn btn-light btn-sm" data-handle="zoomOut" type="button" style="display: none;"><i class="fa fa-search-minus"></i></button>
            <button class="btn btn-light btn-sm" data-handle="reset" type="button" style="display: none;"><i class="fa fa-times"></i></button>
        @endif
    </div>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
</div>


{{-- ########################################## --}}
{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@if ($crud->fieldTypeNotLoaded($field))
    @php
        $crud->markFieldTypeAsLoaded($field);
    @endphp

    {{-- FIELD CSS - will be loaded in the after_styles section --}}
    @push('crud_fields_styles')
        <link href="{{ asset('packages/cropperjs/dist/cropper.min.css') }}" rel="stylesheet" type="text/css" />
        <style>
            .hide {
                display: none;
            }
            .image .btn-group {
                margin-top: 10px;
            }
            img {
                max-width: 100%; /* This rule is very important, please do not ignore this! */
            }
            .img-container, .img-preview {
                width: 100%;
                text-align: center;
            }
            .img-preview {
                float: left;
                margin-right: 10px;
                margin-bottom: 10px;
                overflow: hidden;
            }
            .preview-lg {
                width: 263px;
                height: 148px;
            }

            .btn-file {
                position: relative;
                overflow: hidden;
            }
            .btn-file input[type=file] {
                position: absolute;
                top: 0;
                right: 0;
                min-width: 100%;
                min-height: 100%;
                font-size: 100px;
                text-align: right;
                filter: alpha(opacity=0);
                opacity: 0;
                outline: none;
                background: white;
                cursor: inherit;
                display: block;
            }
        </style>
    @endpush

    {{-- FIELD JS - will be loaded in the after_scripts section --}}
    @push('crud_fields_scripts')
        <script src="{{ asset('packages/cropperjs/dist/cropper.min.js') }}"></script>
        <script src="{{ asset('packages/jquery-cropper/dist/jquery-cropper.min.js') }}"></script>
        <script>
            function bpFieldInitCropperImageElement(element) {
                // Find DOM elements under this form-group element
                var $mainImage = element.find('[data-handle=mainImage]');
                var $uploadImage = element.find("[data-handle=uploadImage]");
                var $hiddenImage = element.find("[data-handle=hiddenImage]");
                var $rotateLeft = element.find("[data-handle=rotateLeft]");
                var $rotateRight = element.find("[data-handle=rotateRight]");
                var $zoomIn = element.find("[data-handle=zoomIn]");
                var $zoomOut = element.find("[data-handle=zoomOut]");
                var $reset = element.find("[data-handle=reset]");
                var $remove = element.find("[data-handle=remove]");
                var $previews = element.find("[data-handle=previewArea]");
                // Options either global for all image type fields, or use 'data-*' elements for options passed in via the CRUD controller
                var options = {
                    viewMode: 2,
                    checkOrientation: false,
                    autoCropArea: 1,
                    responsive: true,
                    preview : element.attr('data-preview'),
                    aspectRatio : element.attr('data-aspectRatio')
                };
                var crop = element.attr('data-crop');

                // Hide 'Remove' button if there is no image saved
                if (!$mainImage.attr('src')){
                    $previews.hide();
                    $remove.hide();
                }
                // Initialise hidden form input in case we submit with no change
                $hiddenImage.val($mainImage.attr('src'));

                // Only initialize cropper plugin if crop is set to true
                if(crop){
                    $mainImage.cropper(options).cropper("reset", true).cropper("replace", this.result);
                    $rotateLeft.click(function() {
                        $mainImage.cropper("rotate", 90);
                    });
                    $rotateRight.click(function() {
                        $mainImage.cropper("rotate", -90);
                    });
                    $zoomIn.click(function() {
                        $mainImage.cropper("zoom", 0.1);
                    });
                    $zoomOut.click(function() {
                        $mainImage.cropper("zoom", -0.1);
                    });
                    $reset.click(function() {
                        $mainImage.cropper("reset");
                    });
                    $rotateLeft.show();
                    $rotateRight.show();
                    $zoomIn.show();
                    $zoomOut.show();
                    $reset.show();
                    $remove.show();
                } else {
                    $remove.click(function() {
                        $mainImage.attr('src','');
                        $hiddenImage.val('');
                        $remove.hide();
                        $previews.hide();
                    });
                }
            }
        </script>
    @endpush
@endif
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
