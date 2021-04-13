// 选择商品分类
function selClass($this) {

    $('.wp_category_list').css('background', '');

    $("#commodityspan").hide();
    $("#commoditydt").show();
    $("#commoditydd").show();
    $this.siblings('li').children('a').attr('class', '');
    $this.children('a').attr('class', 'classDivClick');
    var data_str = '';
    eval('data_str = ' + $this.attr('data-param'));
    $('#parenTid').val(data_str.parenTid);
    $('#count').val(data_str.count);
    $('#dataLoading').show();
    var count = parseInt(data_str.count) + 1;

    $.getJSON('/index.php/console/goods/linkage.html', {parenTid: data_str.parenTid}, function (data) {


        if (data != null) {

            if (count == 4) {
                $('input[nctype="nextStep"]').attr('disabled', false);
            } else {
                $('input[nctype="nextStep"]').attr('disabled', true);
            }

            $('#class_div_' + count).children('ul').html('').end()
                .parents('.wp_category_list:first').removeClass('blank')
                .parents('.sort_list:first').nextAll('div').children('div').addClass('blank').children('ul').html('');
            $.each(data, function (i, n) {

                $('#class_div_' + count).children('ul').append('<li data-param="{parenTid:'
                    + n.id + ',count:' + n.count + '}"><a class="" href="javascript:void(0)"><i class="icon-double-angle-right"></i>'
                    + n.title + '</a></li>')
                    .find('li:last').click(function () {
                    selClass($(this));
                });
            });
        } else {
            $('#class_div_' + data_str.count).parents('.sort_list:first').nextAll('div').children('div').addClass('blank').children('ul').html('');
            disabledButton();
        }
        // 显示选中的分类
        showCheckClass();
        $('#dataLoading').hide();
    });
}

// 按钮
function disabledButton() {
    if ($('#parenTid').val() != '') {
        $('input[nctype="nextStep"]').attr('disabled', false).css('cursor', 'pointer');
    } else {
        $('input[nctype="nextStep"]').attr('disabled', true).css('cursor', 'auto');
    }
}

$(function () {

    //自定义滚定条
    $('#class_div_1').perfectScrollbar();
    $('#class_div_2').perfectScrollbar();
    $('#class_div_3').perfectScrollbar();

    // ajax选择分类
    $('li[nctype="selClass"]').click(function () {
        selClass($(this));
    });

    // 返回分类选择
    $('a[nc_type="return_choose_sort"]').unbind().click(function () {
        $('#parenTid').val('');
        $('#t_id').val('');
        $("#commodityspan").show();
        $("#commoditydt").hide();
        $('#commoditydd').html('');
        $('.wp_search_result').hide();
        $('.wp_sort').show();
    });

    // 常用分类选择 展开与隐藏
    $('#commSelect').hover(
        function () {
            $('#commListArea').show();
        }, function () {
            $('#commListArea').hide();
        }
    );

    // 常用分类选择
    $('#commListArea').find('span[nctype="staple_name"]').die().live('click', function () {
        $('#dataLoading').show();
        $('.wp_category_list').addClass('blank');
        $this = $(this);
        eval('var data_str = ' + $this.parents('li').attr('data-param'));
        $.getJSON('index.php?act=store_goods_add&op=ajax_show_comm&stapleid=' + data_str.stapleid, function (data) {
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
                $('#class_div').find('li[nctype="selClass"]').click(function () {
                    selClass($(this));
                });
                $('#parenTid').val(data.parenTid);
                $("#commodityspan").hide();
                $("#commoditydt").show();
                // 显示选中的分类
                showCheckClass();
                $('#commSelect').children('div:first').html($this.text());
                disabledButton();
                $('#commListArea').hide();
            } else {
                $('.wp_category_list').css('background', '#E7E7E7 none');
                $('#commListArea').find('li').css({'background': '', 'color': ''});
                $this.parent().css({'background': '#3399FD', 'color': '#FFF'});
            }
        });
        $('#dataLoading').hide();
    });

    // ajax删除常用分类
    $('#commListArea').find('a[nctype="del-comm-cate"]').die().live('click', function () {
        $this = $(this);
        eval('var data_str = ' + $this.parents('li').attr('data-param'));
        $.getJSON('index.php?act=store_goods_add&op=ajax_stapledel&staple_id=' + data_str.stapleid, function (data) {
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
});

// 显示选中的分类
function showCheckClass() {
    var str = "";
    var title = "";
    $.each($('a[class=classDivClick]'), function (i) {
        str += $(this).text() + '<i class="icon-double-angle-right"></i>';
    });
    str = str.substring(0, str.length - 39);
    $('#commoditydd').html(str);
    $.each($('a[class=classDivClick]'), function (k) {
            title += $(this).text() + ' >> ';
    });
    title = title.substring(0, title.length - 3);
    $('#goods_title').val(title);
}
