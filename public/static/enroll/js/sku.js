function writeObj(obj){
    var description = "";
    for(var i in obj){
        var property=obj[i];
        description+=i+" = "+property+"\n";
    }
    alert(description);
}
$(function () {

    // 批量设置价格、库存、预警值
    $('.batch > .icon-edit').click(function () {
        $('.batch > .batch-input').hide();
        $(this).next().show();
    });
    $('.batch-input > .close').click(function () {
        $(this).parent().hide();
    });
    $('.batch-input > .ncsc-btn-mini').click(function () {
        var _value = $(this).prev().val();
        var _type = $(this).attr('data-type');
        if (_type == 'priceOriginal' || _type == 'sale_price'|| _type == 'market_price'|| _type == 'priceQuota') {
            _value = number_format(_value, 2);
        }
        if (_type == 'alarm' && _value > 255) {
            _value = 255;
        }
        if (isNaN(_value) && _type != 'sku') {
            _value = 0;
        }
        
        // console.log(_value);
        $('input[data_type="' + _type + '" ]').val(_value);
        $(this).parent().hide();
        $(this).prev().val('');
        if (_type == 'priceOriginal') {
            computePriceOriginal();
        }
        if (_type == 'sale_price') {
            computeSalePrice();
        }
        if (_type == 'market_price') {
            computeMarketPrice();
        }
        if (_type == 'stock') {
            computeStock();
        }
        if (_type == 'alarm') {
            computeAlarm();
        }
        if (_type == 'priceQuota') {
            computePriceQuota();
        }
    });

    // AJAX添加规格值
    $('a[nctype="specAdd"]').click(function () {

        var _parent = $(this).parents('li:first');
        _parent.find('div[nctype="specAdd1"]').hide();
        _parent.find('div[nctype="specAdd2"]').show();
        _parent.find('input').focus();
    });
    // 取消
    $('a[nctype="specAddCancel"]').click(function () {
        var _parent = $(this).parents('li:first');
        _parent.find('div[nctype="specAdd1"]').show();
        _parent.find('div[nctype="specAdd2"]').hide();
        _parent.find('input').val('');
    });
    // 提交
    $('a[nctype="specAddSubmit"]').click(function () {
        var _parent = $(this).parents('li:first');

        eval('var data_str = ' + _parent.attr('data-param'));
        // data_str = _parent.attr('data-param');

        var _input = _parent.find('input');
        _parent.find('div[nctype="specAdd1"]').show();
        _parent.find('div[nctype="specAdd2"]').hide();
        console.log(data_str.url);
        $.getJSON(data_str.url, {

            category_id: data_str.gc_id,
            goods_spec_id: data_str.sp_id,
            spec_title : data_str.spec_title,
            title: _input.val()
        }, function (data) {
            if (data.status) {
                _parent.before('<li><span nctype="input_checkbox"><input type="checkbox" name="sp_val[' + data_str.sp_id + ']['+data_str.spec_title+'][' + data.value_id + ']" nc_type="' + data.value_id + '" value="' + _input.val() + '" /></span><span nctype="pv_name">' + _input.val() + '</span></li>');
                _input.val('');
            }
        });
    });

    // 修改规格名称
    $('dl[nctype="spec_group_dl"]').on('click', 'input[type="checkbox"]', function () {
        pv = $(this).parents('li').find('span[nctype="pv_name"]');
        if (typeof(pv.find('input').val()) == 'undefined') {
            pv.html('<input type="text" maxlength="20" class="text" value="' + pv.html() + '" />');
        } else {
            pv.html(pv.find('input').val());
        }
    });

    $('span[nctype="pv_name"] > input').live('change', function () {
        change_img_name($(this));       // 修改相关的颜色名称
        into_array();           // 将选中的规格放入数组
        goods_stock_set();      // 生成库存配置
    });


    // 常用分类选择 展开与隐藏
    $('#commSelect').hover(
        function(){
            $('#commListArea').show();
        },function(){
            $('#commListArea').hide();
        }
    );

    // 常用分类选择
    $('#commListArea').find('span[nctype="staple_name"]').die().live('click',function() {
        $('#dataLoading').show();
        $('.wp_category_list').addClass('blank');
        $this = $(this);
        eval('var data_str = ' + $this.parents('li').attr('data-param'));
        $.getJSON('index.php?act=store_goods_add&op=ajax_show_comm&stapleid=' + data_str.stapleid, function(data) {
            if (data.done) {
                $('.category_list').children('ul').empty();
                if (data.one.length > 0) {
                    $('#class_div_1').children('ul').append(data.one).parents('.wp_category_list').removeClass('blank');
                }
                if (data.two.length > 0) {
                    $('#class_div_2').children('ul').append(data.two).parents('.wp_category_list').removeClass('blank');
                }
                if (data.three.length > 0) {
                    $('#class_div_3').children('ul').append(data.three).parents('.wp_category_list').removeClass('blank');
                }
                // 绑定ajax选择分类事件
                $('#class_div').find('li[nctype="selClass"]').click(function(){
                    selClass($(this));
                });
                $('#class_id').val(data.gc_id);
                $('#t_id').val(data.type_id);
                $("#commodityspan").hide();
                $("#commoditydt").show();
                // 显示选中的分类
                showCheckClass();
                $('#commSelect').children('div:first').html($this.text());
                disabledButton();
                $('#commListArea').hide();
            } else {
                $('.wp_category_list').css('background', '#E7E7E7 none');
                $('#commListArea').find('li').css({'background' : '', 'color' : ''});
                $this.parent().css({'background' : '#3399FD', 'color' : '#FFF'});
            }
        });
        $('#dataLoading').hide();
    });

    // ajax删除常用分类
    $('#commListArea').find('a[nctype="del-comm-cate"]').die().live('click',function() {
        $this = $(this);
        eval('var data_str = ' + $this.parents('li').attr('data-param'));
        $.getJSON('url'+ data_str.stapleid, function(data) {
            if (data.done) {
                $this.parents('li:first').remove();
                if ($('#commListArea').find('li').length == 1) {
                    $('#select_list_no').show();
                }
            } else {
                alert(data.msg);
            }
        });
    });
})
// 计算商品库存
function computeStock() {
    // 库存
    var _stock = 0;
    $('input[data_type="stock"]').each(function () {
        if ($(this).val() != '') {
            _stock += parseInt($(this).val());
        }
    });
    $('input[name="inventory"]').val(_stock);
}

// 计算商品抢购数量
function computeAlarm() {
    // 抢购数量
    var _alarm = 0;
    $('input[data_type="alarm"]').each(function () {
        if ($(this).val() != '') {
            _alarm += parseInt($(this).val());
        }
    });
    $('input[name="quota_inventory"]').val(_alarm);
}

// 抢购价格计算
function computePriceQuota() {
    // 计算最低价格
    var _price = 0;
    var _price_sign = false;
    $('input[data_type="priceQuota"]').each(function () {
        if ($(this).val() != '' && $(this)) {
            if (!_price_sign) {
                _price = parseFloat($(this).val());
                _price_sign = true;
            } else {
                _price = (parseFloat($(this).val()) > _price) ? _price : parseFloat($(this).val());
            }
        }
    });

    $('input[name="price_quota"]').val(number_format(_price, 2));

}

// 市场计算价格
function computePriceOriginal() {
    // 计算最低价格
    var _price = 0;
    var _price_sign = false;
    $('input[data_type="priceOriginal"]').each(function () {
        if ($(this).val() != '' && $(this)) {
            if (!_price_sign) {
                _price = parseFloat($(this).val());
                _price_sign = true;
            } else {
                _price = (parseFloat($(this).val()) > _price) ? _price : parseFloat($(this).val());
            }
        }
    });

    $('input[name="price_original"]').val(number_format(_price, 2));

}

// 销售价格
function computeSalePrice() {
    // 计算最低价格
    var _price = 0;
    var _price_sign = false;
    $('input[data_type="sale_price"]').each(function () {
        if ($(this).val() != '' && $(this)) {
            if (!_price_sign) {
                _price = parseFloat($(this).val());
                _price_sign = true;
            } else {
                _price = (parseFloat($(this).val()) > _price) ? _price : parseFloat($(this).val());
            }
        }
    });

    $('input[name="sale_price"]').val(number_format(_price, 2));

}

// 计算价格
function computeMarketPrice() {
    // 计算最低价格
    var _price = 0;
    var _price_sign = false;
    $('input[data_type="market_price"]').each(function () {
        if ($(this).val() != '' && $(this)) {
            if (!_price_sign) {
                _price = parseFloat($(this).val());
                _price_sign = true;
            } else {
                _price = (parseFloat($(this).val()) < _price) ? _price : parseFloat($(this).val());
            }
        }
    });

    $('input[name="market_price"]').val(number_format(_price, 2));

}

// 两位小数点
function number_format(num, ext) {
    if (ext < 0) {
        return num;
    }
    num = Number(num);
    if (isNaN(num)) {
        num = 0;
    }
    var _str = num.toString();
    var _arr = _str.split('.');
    var _int = _arr[0];
    var _flt = _arr[1];
    if (_str.indexOf('.') == -1) {

        if (ext == 0) {
            return _str;
        }
        var _tmp = '';
        for (var i = 0; i < ext; i++) {
            _tmp += '0';
        }
        _str = _str + '.' + _tmp;
    } else {
        if (_flt.length == ext) {
            return _str;
        }

        if (_flt.length > ext) {
            _str = _str.substr(0, _str.length - (_flt.length - ext));
            if (ext == 0) {
                _str = _int;
            }
        } else {
            for (var i = 0; i < ext - _flt.length; i++) {
                _str += '0';
            }
        }
    }

    return _str;
}
