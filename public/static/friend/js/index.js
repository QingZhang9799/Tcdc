$('#navBar').on('click', function() {
    $('#nav').toggle()
})
const navBar = document.querySelector('#navBar1')
navBar.onclick = function() {
    $('#nav').toggle()
    window.event ? window.event.cancelBubble = true : e.stopPropagation();
}

$(".swiperCtr li").on("click", function() {
    var lis = $(this).siblings("li");
    for (var i = 0; i < lis.length; i++) {
        $("#" + lis[i].id + "_div").removeClass("show");
        $("#" + lis[i].id + "_div").addClass("hidden");
        $(`#${lis[i].id}_hr`).removeClass("show");
        $(`#${lis[i].id}_hr`).addClass("hidden");
    }
    $("#" + $(this)[0].id + "_div").removeClass("hidden");
    $("#" + $(this)[0].id + "_div").addClass("show");
    $(`#${$(this)[0].id}_hr`).removeClass("hidden");
    $(`#${$(this)[0].id}_hr`).addClass("show");

});