<?php
foreach (get_plugins() as $key=>$value){
    if($value['Name']=='Movies Crawler'){
        $thisversion= $value['Version'];
    }
}

$plugin_path = plugin_dir_url( __DIR__ );
?>

<div class="container-lg mt-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col">
                <div class="card-md">
                    <div class="card-header text-center">
                        <h4 class="">Moives Crawler <?php echo $thisversion?></h4>
                        <div class="d-md-flex justify-content-md-end">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" class="bi bi-github" viewBox="0 0 16 16">
                                <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.012 8.012 0 0 0 16 8c0-4.42-3.58-8-8-8z"/>
                            </svg>
                            <a href="https://github.com/brevis-ng">Brevis Nguyen</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="input-group mb-3">
                            <input type="hidden" id="plugin_path" name="plugin_path" value="<?php echo $plugin_path?>">
                        </div>
                        <div class="input-group mb-3">
                            <span class="input-group-text">Nhập vào JSON API</span>
                            <input type="text" class="form-control" id="jsonapi-url" value="https://api.nguonphim.tv/api.php/provide/vod/?ac=list" placeholder="https://api.nguonphim.tv/api.php/provide/vod/?ac=list">
                            <button class="btn btn-primary" type="button" id="api-check">Kiểm Tra</button>
                        </div>
                        <div id="alert-box" class="alert" style="display: none;" role="alert"></div>
                    </div>
                    <div id="content" class="card-body">
                        <div class="card-title">Thông Tin Nguồn Phim: </div>
                        <ul id="server-info" class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Tổng số bộ phim
                                <span id="movies-total" class="badge bg-primary rounded-pill"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Tổng số page
                                <span id="last-page" class="badge bg-primary rounded-pill"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Số phim mỗi page
                                <span id="per-page" class="badge bg-primary rounded-pill"></span>
                            </li>
                        </ul>
                    </div>
                    <div id="movies-list" class="card-body" style="display: none;">
                        <div class="card-title" id="current-page-crawl">
                            <h4 id="h4-current-page" class="position-absolute">Page 1</h4>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end me-5">
                                <button id="pause-crawl" type="button" class="btn btn-warning">Dừng</button>
                                <button id="resume-crawl" type="button" class="btn btn-warning">Tiếp tục</button>
                            </div>
                        </div>
                        <table class="table" id="movies-table">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Tên Phim</th>
                                    <th scope="col">Thể Loại</th>
                                    <th scope="col">Cập nhật</th>
                                    <th scope="col">Quá trình</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        <button id="roll-crawl" type="button" class="btn btn-success position-absolute">Trộn Link</button>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button id="update-crawl" type="button" class="btn btn-warning">Thu Thập Hôm Nay</button>
                            <button id="full-crawl" type="button" class="btn btn-primary">Thu Thập Toàn Bộ</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>