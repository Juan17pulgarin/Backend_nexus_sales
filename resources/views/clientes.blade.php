<h2>Listado de Clientes</h2>

<table border="1">
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Email</th>
    </tr>

    @foreach($clientes as $c)
    <tr>
        <td>{{ $c->CustomerID }}</td>
        <td>{{ $c->FirstName }} {{ $c->LastName }}</td>
        <td>{{ $c->EmailAddress }}</td>
    </tr>
    @endforeach
</table>
