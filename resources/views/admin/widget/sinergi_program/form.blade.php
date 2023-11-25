@extends('admin.layouts.index')
@include('admin.layouts.components.asset_validasi')

@section('title')
    <h1>
        Sinergi Program
        <small>{{ $action }} Data</small>
    </h1>
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('web_widget') }}">Widget</a></li>
    <li class="breadcrumb-item"><a href="{{ route('web_widget/admin/sinergi_program') }}">Sinergi Program</a></li>
    <li class="active">{{ $action }} Data</li>
@endsection

@section('content')
    @include('admin.layouts.components.notifikasi')

    <div class="box box-info">
        <div class="box-header with-border">
            <a href="{{ route('web_widget/admin/sinergi_program') }}"
                class="btn btn-social btn-info btn-sm btn-sm visible-xs-block visible-sm-inline-block visible-md-inline-block visible-lg-inline-block">
                <i class="fa fa-arrow-circle-left "></i>Kembali ke Sinergi Program
            </a>
        </div>
        <div class="box-body">
            {!! form_open($form_action, 'id="validasi"') !!}
            <div class="box-body">
                <div class="form-group">
                    <label class="control-label">Judul</label>
                    <input type="text" class="form-control input-sm required" id="judul" name="judul"
                        value="{{ $sinergi_program->judul }}" />
                </div>
                <div class="form-group">
                    <label class="control-label">Tautan</label>
                    <input type="url" class="form-control input-sm required" id="tautal" name="tautan"
                        value="{{ $sinergi_program->tautan }}" />
                </div>
                <div class="form-group">
                    <label class="control-label">Gambar</label>
                    <input type="text" class="form-control input-sm required" id="gambar" name="gambar"
                        value="{{ $sinergi_program->gambar }}" />
                </div>
            </div>
            <div class="box-footer">
                <button type="reset" class="btn btn-social btn-danger btn-sm"><i class="fa fa-times"></i> Batal</button>
                <button type="submit" class="btn btn-social btn-info btn-sm pull-right"><i class="fa fa-check"></i>
                    Simpan</button>
            </div>
            </form>
        </div>
    </div>
@endsection
