<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>

<h2>Inicio de Sesión</h2>

@if(session('error'))
    <p style="color:red;">{{ session('error') }}</p>
@endif

<form method="POST" action="/login">
    @csrf
    <input type="email" name="email" placeholder="Email" required>
    <br><br>
    <input type="password" name="password" placeholder="Contraseña" required>
    <br><br>
    <button type="submit">Ingresar</button>
</form>

</body>
</html>
