$(function() {
    $('.gdoc-item').click(function(event) {
        event.preventDefault();
        var percentage = 65 + Math.floor(Math.random() * 25);
        $('.modal-body').html('<div class="progress progress-striped active"><div class="bar" style="width: ' + percentage + '%;"></div></div>');
        $('#myModal').modal('show');
        $('.modal-body').load(this.href, function() {
            $('form#gdoc-raw-data :input').click(function() { this.select(); });
        });
    });
});
