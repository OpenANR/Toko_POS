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
    <h1>TABEL KATEGORI</h1>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Name</th>
                <th>Slug</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($categories as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->slug }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
