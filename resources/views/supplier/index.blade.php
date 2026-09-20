<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <title>Document</title>
</head>

<body>
    <h1>TABEL SUPPLIER</h1>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Nomor Telepon</th>
                <th>Alamat</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($supplier as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->phone }}</td>
                    <td>{{ $item->address }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
