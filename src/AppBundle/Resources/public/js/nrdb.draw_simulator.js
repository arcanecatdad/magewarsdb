(function(draw_simulator, $) {
	var deck = null, initial_size = 0, draw_count = 0, container = null;
	
	draw_simulator.reset = function() {
		if(container) container.empty();
		deck = null;
		initial_size = draw_count = 0;
		update_odds();
		check_draw_type();
		$('#draw-simulator-clear').attr('disabled', true);
	};
	draw_simulator.init = function() {
		container = $('#table-draw-simulator-content');
		deck = [];
		check_draw_type();
		NRDB.data.cards.find({indeck:{'$gt':0},type_code:{'$ne':'identity'}}).forEach(function (card) {
			for(var ex = 0; ex < card.indeck; ex++) {
				deck.push(card);
			}
		});
		initial_size = deck.length;
	};

	function update_odds() {
		for(var i=1; i<=3; i++) {
			var odd = hypergeometric.get_cumul(1, initial_size, i, draw_count);
			$('#draw-simulator-odds-'+i).text(Math.round(100*odd));
		}
	}

	function check_draw_type() {
		identity = NRDB.data.cards.find({indeck:{'$gt':0},type_code:{'$eq':'identity'}}).pop();
		var special_button = $("#draw-simulator-special");
		var special_draw = false;
		switch (identity.code) {
			case "02083":
				special_draw = true;
				special_button.attr("data-draw-type", "andy");
				break;
			case "07029": // MaxX
				special_draw = true;
				special_button.attr("data-draw-type", "maxx");
				break;
			default:
				special_button.hide();
		}
		if (special_draw) {
			special_button.text(identity.title.split(":")[0]).attr("disabled",false).show();
		}
	}
	
	function do_draw(draw, draw_type) {
		for(var pick = 0; pick < draw && deck.length > 0; pick++) {
			var rand = Math.floor(Math.random() * deck.length);
			var spliced = deck.splice(rand, 1);
			var card = spliced[0];
			var card_element;
			if(card.imageUrl) {
				card_element = $('<img src="'+card.imageUrl+'" class="card-image" alt="'+card.title+'">');
			} else {
				card_element = $('<div class="card-proxy"><div>'+card.title+'</div></div>');
			}
			switch (draw_type) {
				case "maxx":
					if ((pick + 1) % 3 != 0) {
						card_element.addClass("trashed");
					}
					break;
			}
			if ($("#draw-simulator-special").attr("data-draw-type") == "andy") {
				$("#draw-simulator-special").attr("disabled",true);
			}
			container.append(card_element);
			draw_count++;
		}
		update_odds();
	}
	
	draw_simulator.handle_click = function(event) {

		event.preventDefault();
		var draw_type = false;
		var id = $(this).attr('id');
		var command = id.substr(15);
		$('#draw-simulator-clear').attr('disabled', false);
		if(command === 'clear') {
			draw_simulator.reset();
			return;
		}
		if(event.shiftKey) {
			draw_simulator.reset();
		}
		if(deck === null) {
			draw_simulator.init();
		}
		var draw;
		if(command === 'all') {
			draw = deck.length;
		} else if(command === 'special') {
			draw_type = $(this).attr('data-draw-type');
			switch (draw_type) {
				case "maxx":
					draw = 3;
					break;
				case "andy":
					draw = 9;
					$(this).attr("disabled", true);
					break;
			}
		} else {
			draw = parseInt(command, 10);
		}
		if(isNaN(draw)) return;
		do_draw(draw, draw_type);

	};
	
	draw_simulator.toggle_opacity = function(event) {
		$(this).css('opacity', 1.5 - parseFloat($(this).css('opacity')));
	};

	$(function () {
		$('#table-draw-simulator').on({click: draw_simulator.handle_click}, 'button.btn');
		$('#table-draw-simulator').on({click: draw_simulator.toggle_opacity}, 'img.card-image, div.card-proxy');
	});
})(NRDB.draw_simulator = {}, jQuery);
