<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><title>Laporan Bulanan {{ $periodLabel }}</title>
<style>@page{size:A4 landscape;margin:12mm}body{margin:0;padding:8px}</style></head>
<body>
@include('reports._content')
@if(! empty($autoPrint))<script>window.addEventListener('load', () => window.print());</script>@endif
</body>
</html>
