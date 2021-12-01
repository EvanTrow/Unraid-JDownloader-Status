// const gpustat_status = () => {
//     $.getJSON('/plugins/gpustat/gpustatus.php', (data) => {
//         if (data) {
//             switch (data["vendor"]) {
//                 case 'NVIDIA':
//                     // Nvidia Slider Bars
//                     $('.gpu-memclockbar').removeAttr('style').css('width', data["memclock"] / data["memclockmax"] * 100 + "%");
//                     $('.gpu-gpuclockbar').removeAttr('style').css('width', data["clock"] / data["clockmax"] * 100 + "%");
//                     $('.gpu-powerbar').removeAttr('style').css('width', parseInt(data["power"].replace("W","") / data["powermax"] * 100) + "%");
//                     $('.gpu-rxutilbar').removeAttr('style').css('width', parseInt(data["rxutil"] / data["pciemax"] * 100) + "%");
//                     $('.gpu-txutilbar').removeAttr('style').css('width', parseInt(data["txutil"] / data["pciemax"] * 100) + "%");
//                     let nvidiabars = ['util', 'memutil', 'encutil', 'decutil', 'fan'];
//                     nvidiabars.forEach(function (metric) {
//                         $('.gpu-'+metric+'bar').removeAttr('style').css('width', data[metric]);
//                     });
//                     if (data["appssupp"]) {
//                         data["appssupp"].forEach(function (app) {
//                             if (data[app + "using"]) {
//                                 $('.gpu-img-span-'+app).css('display', "inline");
//                                 $('#gpu-'+app).attr('title', "Count: " + data[app+"count"] + " Memory: " + data[app+"mem"] + "MB");
//                             } else {
//                                 $('.gpu-img-span-'+app).css('display', "none");
//                                 $('#gpu-'+app).attr('title', "");
//                             }
//                         });
//                     }
//                     break;
//                 case 'Intel':
//                     // Intel Slider Bars
//                     let intelbars = ['3drender', 'blitter', 'video', 'videnh', 'powerutil'];
//                     intelbars.forEach(function (metric) {
//                         $('.gpu-'+metric+'bar').removeAttr('style').css('width', data[metric]);
//                     });
//                     break;
//                 case 'AMD':
//                     $('.gpu-powerbar').removeAttr('style').css('width', parseInt(data["power"] / data["powermax"] * 100) + "%");
//                     $('.gpu-fanbar').removeAttr('style').css('width', parseInt(data["fan"] / data["fanmax"] * 100) + "%");
//                     let amdbars = [
//                         'util', 'event', 'vertex',
//                         'texture', 'shaderexp', 'sequencer',
//                         'shaderinter', 'scancon', 'primassem',
//                         'depthblk', 'colorblk', 'memutil',
//                         'gfxtrans', 'memclockutil', 'clockutil'
//                     ];
//                     amdbars.forEach(function (metric) {
//                         $('.gpu-'+metric+'bar').removeAttr('style').css('width', data[metric]);
//                     });
//                     break;
//             }

//             $.each(data, function (key, data) {
//                 $('.gpu-'+key).html(data);
//             })
//         }
//     });
// };

const jdownloaderstatus_dash = () => {
	// append data from the table into the correct one
	$('#db-box1').append($('.dash_jdownloaderstatus_container').html());

	// reload toggle to get the correct state
	toggleView('dash_jdownloaderstatus_toggle', true);

	// reload sorting to get the stored data (cookie)
	sortTable($('#db-box1'), $.cookie('db-box1'));
};

/*
TODO: Not currently used due to issue with default reset actually working
function resetDATA(form) {
    form.VENDOR.value = "nvidia";
    form.TEMPFORMAT.value = "C";
    form.GPUID.value = "0";
    form.DISPCLOCKS.value = "1";
    form.DISPENCDEC.value = "1";
    form.DISPTEMP.value = "1";
    form.DISPFAN.value = "1";
    form.DISPPCIUTIL.value = "1";
    form.DISPPWRDRAW.value = "1";
    form.DISPPWRSTATE.value = "1";
    form.DISPTHROTTLE.value = "1";
    form.DISPSESSIONS.value = "1";
    form.UIREFRESH.value = "1";
    form.UIREFRESHINT.value = "1000";
    form.DISPMEMUTIL.value = "1";
    form.DISP3DRENDER.value = "1";
    form.DISPBLITTER.value = "1";
    form.DISPVIDEO.value = "1";
    form.DISPVIDENH.value = "1";
    form.DISPINTERRUPT.value = "1";
}
*/
