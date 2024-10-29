jQuery(document).ready(function() {
    if (asp.show) {

        const wrapper = document.createElement('div');
        wrapper.innerHTML = asp.content;

        swal({
            title: asp.title,
            content: wrapper,
            icon: (asp.icon_type != 'empty') ? asp.icon_type : ''
        });
    }
});
