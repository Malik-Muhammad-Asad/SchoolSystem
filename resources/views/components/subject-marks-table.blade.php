<div>
    <table class="w-full">
        @foreach ($record['marks'] as $subject => $marks)
            <tr>
                <td>{{ $subject }}</td>
                @foreach ($marks as $mark)
                    <td>{{ $mark }}</td>
                @endforeach
            </tr>
        @endforeach
    </table>
</div>