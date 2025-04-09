<?php
session_start();

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'cards_printer';
$db_table = 'users';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check user status in database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT status FROM $db_table WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if ($user['status'] !== 'active') { // Assuming 'active' is the status for allowed users
        // User is not active, redirect to login or show error
        header("Location: login.php?error=inactive");
        exit();
    }
} else {
    // User not found in database, redirect to login
    header("Location: login.php?error=notfound");
    exit();
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Free DMA Radius Manager CSV To PDF Converter Online</title>
    <style type="text/css">
        html, body {
            background: #151515;
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Dosis', 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
        }

        #glassbox {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(236, 236, 236, 0.1);
            height: 270px;
            max-height: 270px;
            margin: 30px auto;
            position: relative;
            width: 520px;
            max-width: 520px;
            border-radius: 10px;
            cursor: pointer;
            overflow: hidden;
        }

        #username, #password {
            cursor: move;
            width: 120px;
            border-radius: 2px;
            font-size: 20px;
            position: absolute;
            left: 30px;
            color: white;
            text-shadow: 1px 1px 2px #000;
            padding: 5px;
            background: rgba(0, 0, 0, 0.3);
            user-select: none;
        }

        #password {
            top: 60px;
        }

        .form-style-1 {
            margin: 10px auto;
            max-width: 400px;
            padding: 20px 12px 10px 20px;
            font: 13px "Lucida Sans Unicode", "Lucida Grande", sans-serif;
            color: #ffffff;
        }

        .form-style-1 li {
            padding: 0;
            display: block;
            list-style: none;
            margin: 10px 0 0 0;
        }

        .form-style-1 label {
            margin: 0 0 3px 0;
            padding: 0;
            display: block;
            font-weight: bold;
        }

        .form-style-1 input[type=text],
        .form-style-1 input[type=date],
        .form-style-1 input[type=datetime],
        .form-style-1 input[type=number],
        .form-style-1 input[type=search],
        .form-style-1 input[type=time],
        .form-style-1 input[type=url],
        .form-style-1 input[type=email],
        textarea,
        select {
            box-sizing: border-box;
            border: 1px solid #BEBEBE;
            padding: 7px;
            margin: 0;
            transition: all 0.30s ease-in-out;
            outline: none;
            width: 100%;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-style-1 input[type=file] {
            width: 100%;
            padding: 7px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #BEBEBE;
        }

        .form-style-1 input:focus,
        .form-style-1 textarea:focus,
        .form-style-1 select:focus {
            box-shadow: 0 0 8px #88D5E9;
            border: 1px solid #88D5E9;
        }

        .form-style-1 .required {
            color: red;
        }

        h1 {
            text-align: center;
            font-family: 'Dosis', 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
            color: #fff;
            margin-top: 20px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            color: #fff;
            background: rgba(32, 28, 28, 0.7);
            text-align: center;
            width: 100%;
            padding: 15px 0;
        }

        #profileImage {
            height: 270px;
            width: 520px;
            position: absolute;
            object-fit: cover;
        }

        #imageUpload {
            display: none;
        }

        .btn-success {
            width: 100%;
            padding: 10px;
            font-weight: bold;
        }

        .container-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 100px);
            padding-bottom: 60px;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Dosis:wght@200..800&display=swap" rel="stylesheet">
</head>

<body style="background: url('images/background.jpg') no-repeat center center fixed; background-size: cover;">
<div class="container-wrapper">
    <div class="container">
        <h1>Free DMA Radius Manager CSV To PDF Converter Online</h1>
        <form action="convert.php" id="myform" name="myform" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="ux" id="ux">
            <input type="hidden" name="uy" id="uy">
            <input type="hidden" name="px" id="px">
            <input type="hidden" name="py" id="py">

            <div id="glassbox">
                <img id="profileImage" src="images/bg.jpg" alt="Background">
                <input id="imageUpload" type="file" name="profile_photo" accept="image/*">
                <div id="username">username</div>
                <div id="password">password</div>
            </div>

            <ul class="form-style-1">
                <li>
                    <label>.csv File <span class="required">*</span></label>
                    <input class="form-control" id="csvUpload" type="file" name="csv" accept=".csv" required>
                </li>
                <li>
                    <label>Font size</label>
                    <select name="size" class="form-select field-select">
                        <?php for ($i = 4; $i <= 20; $i++): ?>
                            <option value="<?= $i ?>" <?= $i == 12 ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </li>
                <li>
                    <label>Dimensions</label>
                    <select name="dim" class="form-select field-select">
                        <option value="10">Page(A4) 2x5 cards (10)</option>
                        <option value="21">Page(A4) 3x7 cards (21)</option>
                        <option value="24">Page(A4) 3x8 cards (24)</option>
                        <option value="40">Page(A4) 4x10 cards (40)</option>
                        <option value="50">Page(A4) 5x10 cards (50)</option>
                        <option value="60">Page(A4) 5x12 cards (60)</option>
                        <option value="60">Page(A4) 5x14 cards (70)</option>
                        <!--                        <option value="100">Page(A4) 10x10 cards (100)</option>-->
                    </select>
                </li>
                <li>
                    <button class="btn btn-success" type="submit" name="convert">GET PDF</button>
                </li>
            </ul>
        </form>
    </div>
</div>

<div class="footer">
    <span>Copyright &copy; Othman 2025</span>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!--<script>-->
<!--    $(document).ready(function () {-->
<!--        // Error modal handling-->
<!--        const errorModal = $('#errorModal');-->
<!--        const errorMessage = $('#errorMessage');-->
<!--        const closeErrorModal = $('#closeErrorModal');-->
<!---->
<!--        function showError(message) {-->
<!--            errorMessage.text(message);-->
<!--            errorModal.addClass('active');-->
<!--        }-->
<!---->
<!--        closeErrorModal.click(function () {-->
<!--            errorModal.removeClass('active');-->
<!--        });-->
<!---->
<!--        // Initialize draggable elements-->
<!--        $("#username, #password").draggable({-->
<!--            containment: '#glassbox',-->
<!--            scroll: false,-->
<!--            stop: function (event, ui) {-->
<!--                const id = $(this).attr('id');-->
<!--                const coord = ui.position;-->
<!---->
<!--                if (id === 'username') {-->
<!--                    $("#ux").val(coord.left);-->
<!--                    $("#uy").val(coord.top);-->
<!--                } else {-->
<!--                    $("#px").val(coord.left);-->
<!--                    $("#py").val(coord.top);-->
<!--                }-->
<!--            }-->
<!--        });-->
<!---->
<!--        // Image upload preview-->
<!--        $("#profileImage").click(function () {-->
<!--            $("#imageUpload").click();-->
<!--        });-->
<!---->
<!--        $("#imageUpload").change(function () {-->
<!--            if (this.files && this.files[0]) {-->
<!--                const reader = new FileReader();-->
<!--                reader.onload = function (e) {-->
<!--                    $('#profileImage').attr('src', e.target.result);-->
<!--                }-->
<!--                reader.readAsDataURL(this.files[0]);-->
<!--            }-->
<!--        });-->
<!---->
<!--        // Form submission with enhanced error handling-->
<!--        $("#myform").submit(function (e) {-->
<!--            e.preventDefault();-->
<!---->
<!--            const btn = $("button[name='convert']");-->
<!--            btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...')-->
<!--                .prop('disabled', true);-->
<!---->
<!--            const formData = new FormData(this);-->
<!--            formData.append('username', $("#username").text());-->
<!--            formData.append('password', $("#password").text());-->
<!---->
<!--            $.ajax({-->
<!--                url: 'convert.php',-->
<!--                type: 'POST',-->
<!--                data: formData,-->
<!--                contentType: false,-->
<!--                processData: false,-->
<!--                timeout: 30000, // 30 seconds timeout-->
<!--                success: function (response) {-->
<!--                    try {-->
<!--                        const res = typeof response === 'string' ? JSON.parse(response) : response;-->
<!---->
<!--                        if (res.success && res.filepath) {-->
<!--                            // Create hidden iframe for download-->
<!--                            const iframe = document.createElement('iframe');-->
<!--                            iframe.style.display = 'none';-->
<!--                            iframe.src = res.filepath;-->
<!--                            document.body.appendChild(iframe);-->
<!---->
<!--                            // Clean up after download-->
<!--                            setTimeout(() => {-->
<!--                                document.body.removeChild(iframe);-->
<!--                            }, 5000);-->
<!--                        } else {-->
<!--                            showError(res.message || 'Error generating PDF');-->
<!--                        }-->
<!--                    } catch (e) {-->
<!--                        showError('Invalid server response format');-->
<!--                        console.error('Parsing error:', e, 'Response:', response);-->
<!--                    }-->
<!--                },-->
<!--                error: function (xhr, status, error) {-->
<!--                    let errorMsg = 'Error connecting to server';-->
<!---->
<!--                    if (status === 'timeout') {-->
<!--                        errorMsg = 'Request timed out (30 seconds)';-->
<!--                    } else if (status === 'error') {-->
<!--                        if (xhr.status === 0) {-->
<!--                            errorMsg = 'Network connection failed. Please check your internet connection.';-->
<!--                        } else if (xhr.status === 404) {-->
<!--                            errorMsg = 'Server endpoint not found (404)';-->
<!--                        } else if (xhr.status === 500) {-->
<!--                            errorMsg = 'Internal server error (500)';-->
<!--                        } else if (xhr.responseText) {-->
<!--                            try {-->
<!--                                const res = JSON.parse(xhr.responseText);-->
<!--                                errorMsg = res.message || errorMsg;-->
<!--                            } catch (e) {-->
<!--                                errorMsg = 'Server error: ' + xhr.statusText;-->
<!--                            }-->
<!--                        }-->
<!--                    }-->
<!---->
<!--                    showError(errorMsg);-->
<!--                    console.error('AJAX Error:', status, error, xhr);-->
<!--                },-->
<!--                complete: function () {-->
<!--                    btn.html('GET PDF').prop('disabled', false);-->
<!--                }-->
<!--            });-->
<!--        });-->
<!--    });-->
<!--</script>-->
<script>
    $(document).ready(function () {
        // Error modal handling
        const errorModal = $('#errorModal');
        const errorMessage = $('#errorMessage');
        const closeErrorModal = $('#closeErrorModal');

        function showError(message) {
            errorMessage.text(message);
            errorModal.addClass('active');
        }

        closeErrorModal.click(function () {
            errorModal.removeClass('active');
        });

        // Initialize draggable elements
        $("#username, #password").draggable({
            containment: '#glassbox',
            scroll: false,
            stop: function (event, ui) {
                const id = $(this).attr('id');
                const coord = ui.position;

                if (id === 'username') {
                    $("#ux").val(coord.left);
                    $("#uy").val(coord.top);
                } else {
                    $("#px").val(coord.left);
                    $("#py").val(coord.top);
                }
            }
        });

        // Image upload preview
        $("#profileImage").click(function () {
            $("#imageUpload").click();
        });

        $("#imageUpload").change(function () {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    $('#profileImage').attr('src', e.target.result);
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Form submission with PDF download
        $("#myform").submit(function (e) {
            e.preventDefault();

            const btn = $("button[name='convert']");
            btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...')
                .prop('disabled', true);

            const formData = new FormData(this);
            formData.append('username', $("#username").text());
            formData.append('password', $("#password").text());

            $.ajax({
                url: 'convert.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                timeout: 30000, // 30 seconds timeout
                success: function (response) {
                    try {
                        const res = typeof response === 'string' ? JSON.parse(response) : response;

                        if (res.success && res.filepath) {
                            // Create a temporary link for download
                            const downloadLink = document.createElement('a');
                            downloadLink.href = res.filepath;
                            downloadLink.download = res.filename || 'Radius_Cards.pdf';

                            // Trigger the download
                            document.body.appendChild(downloadLink);
                            downloadLink.click();
                            document.body.removeChild(downloadLink);

                            // Clean up the file after download (optional)
                            setTimeout(() => {
                                $.post('cleanup.php', {filepath: res.filepath});
                            }, 5000);
                        } else {
                            showError(res.message || 'Error generating PDF');
                        }
                    } catch (e) {
                        showError('Invalid server response format');
                        console.error('Parsing error:', e, 'Response:', response);
                    }
                },
                error: function (xhr, status, error) {
                    let errorMsg = 'Error connecting to server';

                    if (status === 'timeout') {
                        errorMsg = 'Request timed out (30 seconds)';
                    } else if (status === 'error') {
                        if (xhr.status === 0) {
                            errorMsg = 'Network connection failed. Please check your internet connection.';
                        } else if (xhr.status === 404) {
                            errorMsg = 'Server endpoint not found (404)';
                        } else if (xhr.status === 500) {
                            errorMsg = 'Internal server error (500)';
                        } else if (xhr.responseText) {
                            try {
                                const res = JSON.parse(xhr.responseText);
                                errorMsg = res.message || errorMsg;
                            } catch (e) {
                                errorMsg = 'Server error: ' + xhr.statusText;
                            }
                        }
                    }

                    showError(errorMsg);
                    console.error('AJAX Error:', status, error, xhr);
                },
                complete: function () {
                    btn.html('GET PDF').prop('disabled', false);
                }
            });
        });
    });
</script>
</body>
</html>