@if($help)
<span class="help-block">
    <i class="fa {{ \Illuminate\Support\Arr::get($help, 'icon') }}"></i>&nbsp;{!! \Illuminate\Support\Arr::get($help, 'text') !!}
</span>
@endif
{{--  Counting Text  --}}
<?php
	$re = '/".*?"|\w+/';
	preg_match_all($re, $attributes, $matches, PREG_SET_ORDER, 0);
	$pairs = [];
	$nkey = "";
	foreach($matches as $key => $val) {
		if(empty($nkey)) {
			$nkey = $val[0];
		} else {
			$pairs[$nkey] = str_replace('"', '', $val[0]);
			$nkey = "";
		}
    }
?>
@if(isset($pairs['maxlength'])&&isset($pairs['name']))
<span style="float:right; margin:0;" class="help-block">
    {{--@lang("admin.word_count")&nbsp;<span class="count">0</span> / {{intval($pairs['maxlength'])}}--}}
    {{__('form.word_count')}}&nbsp;<span class="count">0</span> / {{intval($pairs['maxlength'])}}
</span>
<script>
    $(function(){
        var input = $("[name='{{$pairs['name']}}']");
        var countLbl = $(input).parents('[class="col-sm-8"]').find('[class="count"]');
        setTimeout(function(){
            if (countLbl) {
                countLbl.text(input.val().length);
            }
        }, 200);

        input.on('keyup', function(){
            if (countLbl) {
                countLbl.text($(this).val().length);
            }
        });
    })
</script>
@endif
