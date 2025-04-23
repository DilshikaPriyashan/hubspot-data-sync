<form action="{{ route('company.create') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="file" name="company_csv_file" accept=".csv">
    <button type="submit">Upload</button>
</form>
