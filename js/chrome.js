function complete(version, size, url)
{
    $("#version").text(version);
    $("#size").text(size);
    $("#url").empty();
    $("#url").append(url);

    $(".loader").hide();
    $(".content").slideDown();

    return false;
}

$(document).ready(function() {
    $.fn.selectpicker.defaults = {
        noneSelectedText: '没有选中任何项',
        noneResultsText: '没有找到匹配项',
        countSelectedText: '选中{1}中的{0}项',
        maxOptionsText: ['超出限制 (最多选择{n}项)', '组选择超出限制(最多选择{n}组)'],
        multipleSeparator: ', '
    };
    $("[name='edition']").selectpicker({"autofocus":false});

    ZeroClipboard.config({
        moviePath: "http://shuax-static.qiniudn.com/js/ZeroClipboard.swf",
        hoverClass: "btn-clipboard-hover"
    }),

    $('.highlight').each(function () {
      var btnHtml = '<div class="zero-clipboard"><span class="btn-clipboard">复制</span></div>'
      $(this).before(btnHtml)
    })
    var zeroClipboard = new ZeroClipboard($('.btn-clipboard'))
    var htmlBridge = $('#global-zeroclipboard-html-bridge')

    zeroClipboard.on('load', function () {
      htmlBridge
        .data('placement', 'top')
        .attr('title', '复制到剪贴板')
        .tooltip()
    })
    zeroClipboard.on('dataRequested', function (client) {
      var highlight = $(this).parent().nextAll('.highlight').first()
      client.setText(highlight.text())
    })

    zeroClipboard.on('complete', function () {
      htmlBridge
        .attr('title', '复制成功！')
        .tooltip('fixTitle')
        .tooltip('show')
        .attr('title', '复制到剪贴板')
        .tooltip('fixTitle')
    })

    zeroClipboard.on('noflash wrongflash', function () {
      htmlBridge
        .attr('title', 'Flash required')
        .tooltip('fixTitle')
        .tooltip('show')
    })

    $("#query").click(function(){
        $(".bootstrap-select").removeClass('open');
        $(".content").hide();
        $(".loader").slideDown();

        var branch = $("[name='branch'] option:selected").val();
        var arch = $("[name='arch'] option:selected").val();
        if(!branch)
        {
            return complete("", "", "请选择一个分支")
        }

        if(!arch)
        {
            return complete("", "", "请选择一个架构")
        }

        $.ajax({
            url: "/getchrome",
            type: "post",
            async: true,
            data: {
                "data": JSON.stringify({"branch":branch, "arch":arch})
            },
            dataType: "json",
            error: function() {
                complete("", "", "服务器连接错误。");
            },
            success: function(data) {
                if(data.success)
                {
                    complete(data.version, data.size, data.url.join("\n"));
                }
                else
                {
                    complete("", "", data.message)
                }
            }
        });

        return false;
    })
});