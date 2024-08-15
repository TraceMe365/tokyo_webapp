<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tokyo WebApp</title>
    <!-- BS5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-2.1.3/b-3.1.1/b-html5-3.1.1/datatables.min.css" rel="stylesheet">
    <!-- BS5 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <!-- JQuery -->
    <script src="https://code.jquery.com/jquery-3.6.1.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>
    <!-- DataTables -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-2.1.3/b-3.1.1/b-html5-3.1.1/datatables.min.js"></script>
    <style>
        /* Full page overlay */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999; /* Make sure the overlay is on top of other elements */
        }
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>
<body>
   <div class="container mt-4">
        <!-- Nav pills -->
        <ul class="nav nav-pills">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="pill" href="#home">Report 1</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="pill" href="#menu1">Report 2</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="pill" href="#menu2">Report 3</a>
        </li>
        </ul>

        <!-- Tab panes -->
        <div class="tab-content">
        <div class="tab-pane container active" id="home">
            <div class="row mt-4">
                <h3>Truck duration report</h3>
            </div>
            <div class="row mt-5 mb-3">
                    <div class="col">
                        From:
                        <input type="datetime-local" name="fromReportOne" id="fromReportOne">
                    </div>
                    <div class="col">
                        To:
                        <input type="datetime-local" name="toReportOne" id="toReportOne">
                    </div>
                    <div class="col">
                        Plant:
                        <select class="select" id="reportOnePlant">
                            <option value="peliyagoda">Peliyagoda Plant</option>
                            <option value="kandy">Kandy Plant</option>
                            <option value="trinco">Trinco Plant</option>
                            <option value="anuradhapura">Anuradhapura Plant</option>
                            <option value="negombo">Negombo Plant</option>
                            <option value="ankanda">Ankanda Plant</option>
                            <option value="mahiyangana">Mahiyangana Plant</option>
                            <option value="meethotamulla">Meethotamulla Plant</option>
                            <option value="waligama">Waligama Plant</option>
                            <option value="jaffna">Jaffna Plant</option>
                            <option value="rathmalana">Rathmalana Plant</option>
                        </select>
                    </div>
                    <div class="col">
                        <button class="btn btn-danger" id="generateReportOne">Generate</button>
                    </div>
            </div>
            <div class="row mb-3">
            <table id="reportOneTable" class="table table-striped mt-3">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Truck</th>
                    <th>Plant</th>
                    <th>Site</th>
                    <th>Plant Out Time</th>
                    <th>Site In Time</th>
                    <th>Site Out Time</th>
                    <th>Plant In Time</th>
                    <th>Duration (Site Out Time - Plant Out Time) </th>
                </tr>
                </thead>
                <tbody id="reportOneTableBody">

                </tbody>
            </table>
            </div>
        </div>
        <div class="tab-pane container fade" id="menu1">
            <div class="row mt-5">
                    <h3>Pump car idling report</h3>
            </div>
                <div class="row mt-5 mb-3">
                    <div class="col">
                        From:
                        <input type="datetime-local" name="fromReportTwo" id="fromReportTwo">
                    </div>
                    <div class="col">
                        To:
                        <input type="datetime-local" name="toReportTwo" id="toReportTwo">
                    </div>
                    <div class="col">
                        <button class="btn btn-danger" id="generateReportTwo">Generate</button>
                    </div>
                </div>
                <div class="row mb-3">
                <table id="reportTwoTable" class="table table-striped mt-3">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Pump Car</th>
                        <th>Plant</th>
                        <th>Site</th>
                        <th>Plant Out Time</th>
                        <th>Site In Time</th>
                        <th>Pump Idle Time</th>
                        <th>First Truck In Time</th>
                        <th>Truck</th>
                    </tr>
                    </thead>
                    <tbody id="reportTwoTableBody">

                    </tbody>
                </table>
                </div>
        </div>
        <div class="tab-pane container fade" id="menu2">
            <div class="row mt-5">
                    <h3>Pump car cancelling report</h3>
            </div>
            <div class="row mt-5 mb-3">
                    <div class="col">
                        From:
                        <input type="datetime-local" name="fromReportThree" id="fromReportThree">
                    </div>
                    <div class="col">
                        To:
                        <input type="datetime-local" name="toReportThree" id="toReportThree">
                    </div>
                    <div class="col">
                        <button class="btn btn-danger" id="generateReportThree">Generate</button>
                    </div>
            </div>
            <div class="row mb-3">
                <table id="reportThreeTable" class="table table-striped mt-3">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Plant</th>
                        <th>Scheduled customer </th>
                        <th>Planned dispatch time </th>
                        <th>Re scheduled time</th>
                        <th>Cancelled / postponed reason</th>
                    </tr>
                    </thead>
                    <tbody id="reportThreeTableBody">

                    </tbody>
                </table>
                </div>
        </div>
        </div>
   </div>
   <div class="overlay" id="loader" style="display:none">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</body>
<script>
    $(document).ready(function(){
        $("#generateReportOne").on('click',async function(){
            $("#loader").css("display","flex")
            var from = $("#fromReportOne").val();
                from = convertToUnix(from);
            var to   = $("#toReportOne").val();
                to = convertToUnix(to);
            var plant = $("#reportOnePlant").val();
            var form = new FormData();
            
            form.append("from", from);
            form.append("to", to);
            form.append("plant", plant);
            
            var settings = {
                "url": "http://localhost:8000/api/getReportOne",
                "method": "POST",
                "timeout": 0,
                "processData": false,
                "mimeType": "multipart/form-data",
                "contentType": false,
                "data": form
            };

            var tableData = [];

            await $.ajax(settings).done(function (response) {
                let data = JSON.parse(response);
                if(data.data){
                    $("#reportOneTableBody").html("");
                    (data.data).forEach(function(row,index){
                        var date = new Date(row['tokyo_plant_in_time']);
                        var formattedDate = date.toISOString().split('T')[0];
                        tableData.push([
                            formattedDate,
                            row['tokyo_vehicle_name'] != null ? row['tokyo_vehicle_name'] : "N/A",
                            row['tokyo_location_name'] != null ? row['tokyo_location_name'] : "N/A",
                            row['tokyo_site_name'] != null ? row['tokyo_site_name'] : "N/A",
                            row['tokyo_plant_out_time'] != null ? row['tokyo_plant_out_time'] : "N/A",
                            row['tokyo_site_in_time'] != null ? row['tokyo_site_in_time'] : "N/A",
                            row['tokyo_site_out_time'] != null ? row['tokyo_site_out_time'] : "N/A",
                            row['tokyo_plant_in_time'] != null ? row['tokyo_plant_in_time'] : "N/A",
                            row['tokyo_site_out_plan_out_duration'] != null ? row['tokyo_site_out_plan_out_duration'] : "N/A"
                        ]);
                    })
                }
            });
            // Destroy existing DataTable if it exists
            if ($.fn.DataTable.isDataTable('#reportOneTable')) {
                $('#reportOneTable').DataTable().clear().destroy();
            }

            $("#reportOneTable").DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print',
                ],
                exportOptions: {
                    title: null
                },
                data: tableData, // Use the prepared data array
                columns: [
                    { title: "Date" },
                    { title: "Vehicle Name" },
                    { title: "Location Name" },
                    { title: "Site Name" },
                    { title: "Plant Out Time" },
                    { title: "Site In Time" },
                    { title: "Site Out Time" },
                    { title: "Plant In Time" },
                    { title: "Site Out Plan Out Duration" }
                ]
            });
            
            $("#loader").css("display","none")
        })

        $("#generateReportTwo").on('click',async function(){
            $("#loader").css("display","flex")
            var from = $("#fromReportTwo").val();
                from = convertToUnix(from);
            var to   = $("#toReportTwo").val();
                to = convertToUnix(to);

            var form = new FormData();
            
            form.append("from", from);
            form.append("to", to);
            
            var settings = {
                "url": "http://localhost:8000/api/getReportTwo",
                "method": "POST",
                "timeout": 0,
                "processData": false,
                "mimeType": "multipart/form-data",
                "contentType": false,
                "data": form
            };
            var tableData = [];
            await $.ajax(settings).done(function (response) {
                let data = JSON.parse(response);
                console.log(data);
                if(data.data){
                    $("#reportTwoTableBody").html("");
                    (data.data).forEach(function(row,index){
                        var date = new Date(row['tokyo_plant_out_time']);
                        var formattedDate = date.toISOString().split('T')[0];
                        tableData.push([
                            formattedDate,
                            row['tokyo_pump_car_name'],
                            row['tokyo_location_name'],
                            row['tokyo_site_name']!=null?row['tokyo_site_name']:"N/A",
                            row['tokyo_plant_out_time'],
                            row['tokyo_site_in_time'],
                            row['tokyo_pump_idle_time']!=null?row['tokyo_pump_idle_time']:"N/A",
                            row['tokyo_first_truck_in_time']!=null?row['tokyo_first_truck_in_time']:"N/A",
                            row['tokyo_first_truck_in_name']!=null?row['tokyo_first_truck_in_name']:"N/A"
                        ]);
                    })
                }
            });
            // Destroy existing DataTable if it exists
            if ($.fn.DataTable.isDataTable('#reportTwoTable')) {
                $('#reportTwoTable').DataTable().clear().destroy();
            }

            $("#reportTwoTable").DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print',
                ],
                exportOptions: {
                    title: null
                },
                data: tableData, // Use the prepared data array
                columns: [
                    { title: "Date" },
                    { title: "Pump Car" },
                    { title: "Plant" },
                    { title: "Site" },
                    { title: "Plant Out Time" },
                    { title: "Site In Time" },
                    { title: "Pump Idle Time" },
                    { title: "First Truck In Time" },
                    { title: "Truck Name" }
                ]
            });
            $("#loader").css("display","none")
        })

        $("#generateReportThree").on('click',async function(){
            $("#loader").css("display","flex")
            var from = $("#fromReportThree").val();
                from = convertToUnix(from);
            var to   = $("#toReportThree").val();
                to = convertToUnix(to);

            var form = new FormData();
            
            form.append("from", from);
            form.append("to", to);
            
            var settings = {
                "url": "http://localhost:8000/api/getReportThree",
                "method": "POST",
                "timeout": 0,
                "processData": false,
                "mimeType": "multipart/form-data",
                "contentType": false,
                "data": form
            };

            await $.ajax(settings).done(function (response) {
                let data = JSON.parse(response);
                console.log(data);
                if(data.data){
                    $("#reportThreeTableBody").html("");
                    (data.data).forEach(function(row,index){
                        var date = new Date(row['date']);
                        var formattedDate = date.toISOString().split('T')[0];
                        $("#reportThreeTableBody").append(`<tr>
                        <td>${formattedDate}</td>
                        <td>${row['plant']}</td>
                        <td><input type="text" id="${row['id']}-sch_cus" data-row="${index}" data-column="2" class="input"></td>
                        <td><input type="text" id="${row['id']}-pln_dis" data-row="${index}" data-column="3" class="input"></td>
                        <td><input type="text" id="${row['id']}-resch" data-row="${index}" data-column="4" class="input"></td>
                        <td><input type="text" id="${row['id']}-canc" data-row="${index}" data-column="5" class="input"></td>
                        </tr>`);    
                    })
                }
            });
            if (!$.fn.DataTable.isDataTable('#reportThreeTable')) {
                var table = $("#reportThreeTable").DataTable({
                    dom: 'Bfrtip',
                    buttons: [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ]
                });
                $(".input").on('change',function(){
                    var value = $(this).val();
                    var row = $(this).data('row');
                    var column = $(this).data('column');
                    table.cell(row, column).data(value).draw();
                })
            }
            $("#loader").css("display","none")
            
        })
        
        function convertToUnix(dateStr){
            const date = new Date(dateStr);
            return unixTimestamp = Math.floor(date.getTime() / 1000);
        }
    });
</script>
</html>