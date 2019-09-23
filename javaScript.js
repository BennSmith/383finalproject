$(document).ready(function(){
	$('#errorDiv').css("display","none");
	// Variable to store the token
	var token = "";

	$('#form').on('submit', function(a){
		var user = $("#user").val();
		var password = $("#password").val();
		$.ajax({type:'POST',
			url:'http://ceclnx01.cec.miamioh.edu/~smithb25/cse383/finalProject/rest.php/v1/user/',
			contentType:'application/json',
			data: JSON.stringify({
				"user": user,
				"password": password
			}),
			success: function(text){
				console.log(text);
				if(text.status ==="OK"){
					$('#errorDiv').css("display","none");
					$('#formDiv').css("display","none");
                    $('#dataTable').css("display","block");

					token = text.token;
					reload();
				}else{
					console.log("Authentication Failed");
					$('#errorDiv').css("display","block");
				}
			},
			error: function(xhr){
				alert("there was an error");
			}
		});
		a.preventDefault();
	});	
	
	// function which gets the list of items from the rest api, and generates item buttons
	function getItemList() {
        $("#item-buttons").html("");
        $.ajax({
            type: 'GET',
            url: "http://ceclnx01.cec.miamioh.edu/~smithb25/cse383/finalProject/rest.php/v1/items",
            success: function(text) {
                var items = text.items;
                console.log(items);
                for(var i = 0; i < items.length; i ++) {
                    console.log(items[i].item);
                    var button = "<button id='" + items[i].pk +  "'>" + items[i].item + "</button>";
                    $("#item-buttons").append(button);
				}	
				genButtons(); // this generates the on click handlers
            },
            error: function(result) {
                console.log(result);
            }
        });
	}

	 // generate the user's item summary
	 function genSummary() {
        $("#summary tbody").html("");
        $.ajax({
            type: 'GET',
            url: "http://ceclnx01.cec.miamioh.edu/~smithb25/cse383/finalProject/rest.php/v1/itemsSummary/" + token,
            success: function(text) {
                console.log(text);
                var items = text.items
                for(var i = 0; i < items.length; i ++) {
                    var row = "<tr><td>"+items[i].item+"</td><td>"+items[i].count+"</td></tr>"
                    $("#summary tbody").append(row);
                }
            },
            error: function(text) {
                console.log(text);
            }
        })
	}

	// function which generates the diary of item consumption for a user
	function genDiary() {
        $("#diary tbody").html("");
        $.ajax({
            type: 'GET',
            url: "http://ceclnx01.cec.miamioh.edu/~smithb25/cse383/finalProject/rest.php/v1/items/" + token,
            success: function(text) {
                console.log(text);
                var items = text.items;
                for(var i = 0; i < items.length; i ++) {
                    var row = "<tr><td>"+items[i].item+"</td><td>"+items[i].timestamp+"</td></tr>"
                    $("#diary tbody").append(row);
                }
            },
            error: function(text) {
                console.log(text);
            }
        });
	}
	
	function genButtons() {
        $('#item-buttons button').each(function () {
            $(this).on("click", function() {
                var itemFK = $(this).attr("id");
                console.log(itemFK);
                $.ajax({
                    type: 'POST',
                    url: "http://ceclnx01.cec.miamioh.edu/~smithb25/cse383/finalProject/rest.php/v1/items",
                    contentType: 'application/json',
                    data: JSON.stringify({
                        "token": token,
                        "itemFK": itemFK
                    }),
                    success: function(result) {
                        console.log(result);
                        if(result.status === "OK") {
                            genSummary();
                            genDiary();
                        }
                    },
                    error: function(result) {
                        console.log(result);
                        $('h2').text = result.status;
                    }
                })
            });
        });
    }
	

	// function which runs all functions needed to reload data within page
	function reload() {
		getItemList();
		genButtons();
		genSummary();
		genDiary();
	}


	//reload();
});


