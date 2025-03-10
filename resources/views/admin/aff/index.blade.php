@extends('admin.layouts')
@section('css')
    <link href="/assets/global/vendor/bootstrap-table/bootstrap-table.min.css" rel="stylesheet">
@endsection
@section('content')
    <div class="page-content container-fluid">
        <div class="panel">
            <div class="panel-heading">
                <h3 class="panel-title">提现申请列表</h3>
            </div>
            <div class="panel-body">
                <div class="form-row">
                    <div class="form-group col-lg-2 col-sm-4">
                        <input type="text" class="form-control" name="email" value="{{Request::input('email')}}" id="email" placeholder="申请账号"/>
                    </div>
                    <div class="form-group col-lg-2 col-sm-4">
                        <select class="form-control" name="status" id="status" onChange="Search()">
                            <option value="" hidden>状态</option>
                            <option value="-1">驳回</option>
                            <option value="0">待审核</option>
                            <option value="1">审核通过待打款</option>
                            <option value="2">已打款</option>
                        </select>
                    </div>
                    <div class="form-group col-lg-1 col-sm-4 btn-group">
                        <button class="btn btn-primary" onclick="Search()">搜 索</button>
                        <a href="{{route('admin.aff.index')}}" class="btn btn-danger">重 置</a>
                    </div>
                </div>
                <table class="text-md-center" data-toggle="table" data-mobile-responsive="true">
                    <thead class="thead-default">
                    <tr>
                        <th> #</th>
                        <th> 申请时间</th>
                        <th> 申请账号</th>
                        <th> 申请提现金额</th>
                        <th> 状态</th>
                        <th> 处理时间</th>
                        <th> 操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($applyList as $apply)
                        <tr>
                            <td> {{$apply->id}} </td>
                            <td> {{$apply->created_at}} </td>
                            <td>
                                @if(empty($apply->user))
                                    【账号已删除】
                                @else
                                    @can('admin.user.index')
                                        <a href="{{route('admin.user.index', ['id'=>$apply->user_id])}}" target="_blank">
                                            {{$apply->user->email}}
                                        </a>
                                    @else
                                        {{$apply->user->email}}
                                    @endcan
                                @endif
                            </td>
                            <td> ￥{{$apply->amount}} </td>
                            <td>
                                @if($apply->status === -1)
                                    <span class="badge badge-lg badge-danger"> 驳 回 </span>
                                @elseif($apply->status === 0)
                                    <span class="badge badge-lg badge-info"> 待审核 </span>
                                @elseif($apply->status === 2)
                                    <span class="badge badge-lg badge-success"> 已打款 </span>
                                @else
                                    <span class="badge badge-lg badge-default"> 待打款 </span>
                                @endif
                            </td>
                            <td> {{$apply->created_at == $apply->updated_at ? '' : $apply->updated_at}} </td>
                            <td>
                                @canany(['admin.aff.setStatus', 'admin.aff.detail'])
                                    <div class="btn-group">
                                        @can('admin.aff.setStatus')
                                            @if($apply->status === 0)
                                                <a href="javascript:setStatus('{{$apply->id}}','1')" class="btn btn-sm btn-success">
                                                    <i class="icon wb-check" aria-hidden="true"></i>通过</a>
                                                <a href="javascript:setStatus('{{$apply->id}}','-1')" class="btn btn-sm btn-danger">
                                                    <i class="icon wb-close" aria-hidden="true"></i>驳回</a>
                                            @elseif($apply->status === 1)
                                                <a href="javascript:setStatus('{{$apply->id}}','2')" class="btn btn-sm btn-primary">
                                                    <i class="icon wb-check-circle" aria-hidden="true"></i>已打款</a>
                                            @endif
                                        @endcan
                                        @can('admin.aff.detail')
                                            <a href="{{route('admin.aff.detail', $apply->id)}}" class="btn btn-sm btn-default">
                                                <i class="icon wb-search"></i></a>
                                        @endcan
                                    </div>
                                @endcanany
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="panel-footer">
                <div class="row">
                    <div class="col-sm-4">
                        共 <code>{{$applyList->total()}}</code> 个申请
                    </div>
                    <div class="col-sm-8">
                        <nav class="Page navigation float-right">
                            {{$applyList->links()}}
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('javascript')
    <script src="/assets/global/vendor/bootstrap-table/bootstrap-table.min.js"></script>
    <script src="/assets/global/vendor/bootstrap-table/extensions/mobile/bootstrap-table-mobile.min.js"></script>
    <script>
      $(document).ready(function() {
        $('#status').val({{Request::input('status')}});
      });

      //回车检测
      $(document).on('keypress', 'input', function(e) {
        if (e.which === 13) {
          Search();
          return false;
        }
      });

      // 搜索
      function Search() {
        window.location.href = '{{route('admin.aff.index')}}?email=' + $('#email').val() + '&status=' + $('#status option:selected').val();
      }

      @can('admin.aff.setStatus')
      // 更改状态
      function setStatus(id, status) {
        $.ajax({
          method: 'PUT',
          url: '{{route('admin.aff.setStatus','')}}/' + id,
          data: {
            _token: '{{csrf_token()}}',
            status: status,
          },
          dataType: 'json',
          success: function(ret) {
            if (ret.status === 'success') {
              swal.fire({title: ret.message, icon: 'success', timer: 1000, showConfirmButton: false}).then(() => window.location.reload());
            } else {
              swal.fire({title: ret.message, icon: 'error'}).then(() => window.location.reload());
            }
          },
          error: function(data) {
            let str = '';
            const errors = data.responseJSON;
            if ($.isEmptyObject(errors) === false) {
              $.each(errors.errors, function(index, value) {
                str += '<li>' + value + '</li>';
              });
              swal.fire({title: '提示', html: str, icon: 'error', confirmButtonText: '{{trans('home.ticket_confirm')}}'});
            }
          },
        });
      }
        @endcan
    </script>
@endsection
