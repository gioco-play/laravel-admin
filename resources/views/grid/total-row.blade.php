<tfoot>
    <tr class="subtotal">
        @foreach($columns as $column)
            @if(strtolower($column['value']) === 'total' )
            <td class="{{ $column['class'] }}">{{__('subtotal')}}</td>
            @elseif ($column['value'] >"")
            <td class="{{ $column['class'] }} subtotal">{!! $column['value'] !!}</td>
            @else
            <td class="{{ $column['class'] }}">{!! $column['value'] !!}</td>
            @endif
        @endforeach
    </tr>
    <tr class="total">
        @foreach($columns as $column)
            @if(strtolower($column['value']) === 'total' )
            <td class="{{ $column['class'] }}">{{__('total')}}</td>
            @else
            <td class="{{ $column['class'] }}">{!! $column['value'] !!}</td>
            @endif
        @endforeach
    </tr>
</tfoot>
    
<script>
    (function(){
        $('tfoot').find("td.subtotal").each(function(){
            var ix = $(this)[0].cellIndex+1;
            var val = Array.from($("tbody").find("tr td:nth-child("+ix+")").map(function(){
                val = parseFloat($(this).text());
                return isNaN(val)?0:val;
            })).reduce((a,b)=>a+b);
            $('tfoot').find("td.subtotal:nth-child("+ix+")").text(val.toFixed(4));
        });
    })();
</script>
    