<!DOCTYPE html>
<html>
<head>
    <title>iRespond Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>

        body{
            background:#f3f3f3;
        }

        .service-card{

            position:relative;
            margin:35px 0;
        }

        .service-info{

            margin-left:60px;

            background:#3559c7;

            color:white;

            border-radius:18px;

            padding:20px 25px 20px 70px;

        }

        .service-icon{

            position:absolute;

            left:0;
            top:50%;

            transform:translateY(-50%);

            width:100px;
            height:100px;

            border-radius:50%;

            background:red;

            display:flex;

            justify-content:center;
            align-items:center;

            color:white;

            font-weight:bold;

            font-size:20px;

            box-shadow:0 5px 15px rgba(0,0,0,.3);
        }

    </style>

</head>

<body>

<div class="container py-4">

    <div class="service-card">

        <div class="service-icon">
            🔥
        </div>

        <div class="service-info">

            <h5>Fire</h5>

            <p class="mb-0">
                Report fire incidents immediately.
            </p>

        </div>

    </div>

    <div class="service-card">

        <div class="service-icon">
            🏥
        </div>

        <div class="service-info">

            <h5>Incident</h5>

            <p class="mb-0">
                Request medical assistance.
            </p>

        </div>

    </div>

    <div class="service-card">



    </div>

    <div class="service-card">

        <div class="service-icon">
            🌪
        </div>

        <div class="service-info">

            <h5>Natural Disaster</h5>

            <p class="mb-0">
                Floods, typhoons, earthquakes and more.
            </p>

        </div>

    </div>

</div>

</body>
</html>