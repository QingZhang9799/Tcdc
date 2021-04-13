// 全选所有
function CheckAll(val) {
    $("input[name='id[]']").each(function () {
        this.checked = val;
    });
}
// 删除所有(不包含子分类)
function DelAllNone(url) {
    $("#form").attr("action", url + "?action=delall2").submit();

}
// 删除选中提示
function ConfDelAll(i) {
    var tips = Array();
    tips[0] = "确定要删除选中的信息吗？";

    if ($("input[type='checkbox'][name!='id'][name^='id']:checked").size() > 0) {
        if (confirm(tips[i])) return true;
        else return false;
    }
    else {
        layer.msg('没有任何选中信息！');
        return false;
    }
}
// 展开合并下级
function DisplayRows(id) {
    var rowpid = $("tr[rel='rowpid_" + id + "']");
    var rowid = $("span[id='rowid_" + id + "']");

    if (rowid.attr("class") == "minusSign") {
        rowpid.slideUp(200);
        rowid.attr("class", "plusSign");
    }
    else {
        rowpid.slideDown(200);
        rowid.attr("class", "minusSign");
    }

}

// 更改审核状态
function CheckInfo(stateUrl) {
    $.ajax({
        url: stateUrl,
        dataType: 'html',
        success: function (data) {
            if (data == 'false') {
                layer.msg('更新状态失败');
            } else {
                layer.msg('更新状态成功');
            }

        }
    });

}
// 排列顺序
function UpOrderID(url) {

    $("#form").attr("action", url).submit();
}

// 跳转
function Jump() {
    window.location.reload();
    //window.location.href="/member/borrowdetail?id="+id+"#chip-1";
}