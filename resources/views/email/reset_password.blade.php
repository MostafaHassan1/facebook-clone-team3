<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>

<div>
    Hi {{ $name }},
    <br> this is reset password page
    <br> Please write new password
    <br>
    <!-- get password from box here -->
    <a href="{{ url('api/auth/reset_pass',$token)}}">Set New Password</a>

    <br/>
</div>

</body></html>
