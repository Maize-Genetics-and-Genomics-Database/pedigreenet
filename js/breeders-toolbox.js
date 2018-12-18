var cy;
$(function () {
    'use strict';
    // Hide error mesgs
    $("#caveat-error").hide();
    // function definitions
    function getAllUrlParams(url) {
        // get query string from url (optional) or window
        var queryString = url ? url.split('?')[1] : window.location.search.slice(1);
        // we'll store the parameters here
        var obj = {};
        // if query string exists
        if (queryString) {
            // stuff after # is not part of query string, so get rid of it
            queryString = queryString.split('#')[0];
            // split our query string into its component parts
            var arr = queryString.split('&');
            for (var i=0; i<arr.length; i++) {
                // separate the keys and the values
                var a = arr[i].split('=');
                // in case params look like: list[]=thing1&list[]=thing2
                var paramNum = undefined;
                var paramName = a[0].replace(/\[\d*\]/, function(v) {
                    paramNum = v.slice(1,-1);
                    return '';
                });
                // set parameter value (use 'true' if empty)
                var paramValue = typeof(a[1])==='undefined' ? true : a[1];
                // if parameter name already exists
                if (obj[paramName]) {
                    // convert value to array (if still string)
                    if (typeof obj[paramName] === 'string') {
                        obj[paramName] = [obj[paramName]];
                    }
                    // if no array index number specified...
                    if (typeof paramNum === 'undefined') {
                        // put the value on the end of the array
                        obj[paramName].push(paramValue);
                    }
                    // if array index number specified...
                    else {
                        // put the value at that index number
                        obj[paramName][paramNum] = paramValue;
                    }
                }
                // if param name doesn't exist yet, set it
                else {
                    obj[paramName] = paramValue;
                }
            }
        }
        return obj;
    }
    function disperse(cy){
        cy.nodes().positions(function( node, i ){
            if (cy.nodes().hasOwnProperty(node)) {
                return {
                    x: cy.nodes()[node].position().x * 1.5,
                    y: cy.nodes()[node].position().y * 1.5
                };
            }
            else{
                return {
                    x: 0,
                    y: 0
                };
            }
        });
    }
    function compress(cy){
        cy.nodes().positions(function( node, i ){
            if (cy.nodes().hasOwnProperty(node)) {
                return {
                    x: cy.nodes()[node].position().x / 1.5,
                    y: cy.nodes()[node].position().y / 1.5
                };
            }
            else{
                return {
                    x: 0,
                    y: 0
                };
            }
        });
    }
    var networkJson = $('#cytoscape-data').text();
    if (!networkJson) {
        networkJson = '{}';
    }


    var elements = JSON.parse(networkJson);
    // Map all ID's to lower case:
    for (var ele in elements.nodes){
        if (elements.nodes.hasOwnProperty(ele) && isNaN(elements.nodes[ele].data.id)) {
           elements.nodes[ele].data.id = elements.nodes[ele].data.id.toLowerCase();
        }
    }
    for (var ele in elements.edges) {
        if (elements.edges.hasOwnProperty(ele)) {
            elements.edges[ele].data.source = elements.edges[ele].data.source.toLowerCase();
            elements.edges[ele].data.target = elements.edges[ele].data.target.toLowerCase();
        }
    }
    var avoidOverlap = false;
    if (elements.nodes !== undefined && elements.nodes.length == 2) {        
        //HACK: For some reason when there are 2 nodes they are initially rendered on top of each 
        //other in the concentric layout when avoidOverlap = false
        avoidOverlap = true;
    }
    var cmLink = $('#cm-link');
    var currentLine = $('.current-line');
    var instantiate = {
        elements: elements,
        style: cytoscape.stylesheet()
            .selector('node')
            .css({
                'content': 'data(name)',
                'label': 'data(name)',
                'text-valign': 'center',
                'color': 'white',
                'text-outline-width': 2,
                'text-outline-color': '#888'
            })
            .selector('edge')
            .css({
                'content': 'data(name)',
                'target-arrow-shape': 'triangle',
                'target-arrow-color': 'black',
                'line-color': 'black'
            })
            .selector(':selected')
            .css({
                'background-color': 'red',
                'line-color': 'black',
                'target-arrow-color': 'black',
                'source-arrow-color': 'black'
            })
            .selector('.faded')
            .css({
                'opacity': 0.25,
                'text-opacity': 0
            }),
        layout: {
            name: $('#initial-layout').text(),
            padding: 10,
            fit: false,
            avoidOverlap: avoidOverlap,
        },
        minZoom: .35,
        maxZoom: 5,
        wheelSensitivity: 0.33,
        ready: function () {
            cy = this;
            var params = getAllUrlParams(window.location.href);
            for (var id in params) {
                if (params.hasOwnProperty(id)) {
                    cy.elements('node#' + params[id].toLowerCase()).style('background-color', "red");
                }
            }

            cy.on('tap', 'node', function (evt) {
                var original = evt.originalEvent;
                var node = evt.cyTarget;
                var name = node.attr('name');
                $.ajax({
                    url: '/breeders_toolbox',
                    data: {
                        id: name
                    },
                    success: function (res) {
                        res = JSON.parse(res);
                        if (res.id != null) {
                            currentLine.text(name);
                            cmLink.attr('href', '/data_center/stock?id=' + res.id);
                            contextMenu.showMenu(evt.originalEvent);
                        }
                    }
                });
            });
        }
    };

    if (instantiate.elements.nodes !== undefined && instantiate.elements.nodes.length > 0) {
        $('#cytoscape').cytoscape(instantiate);
    }
    if ($('#initial-layout').text() != "cose" && $('#initial-layout').text().length > 3) {
       $('#png-data').val(cy.png());
        //The png export feature does not work with the cose layout for some reason
        var thumbnail = document.getElementById("cytoscape-thumbnail");
        var png_options = {
            scale: 1,
            maxWidth: 600,
            maxHeight: 600,
            full: true
        };
            var base64string = cy.png(png_options);
            var imageData = base64string.split(',')[1];
            $.ajax({
                url: '/breeders_toolbox',
                type: 'post',
                data: {
                    imageData: imageData,
                    stock: $('#node-center').text()
                }
            });
    }
    $('#cyto-layout').on('change', function () {
        var layout = $(this).val();
        cy.layout({name: layout});
    });

    $('#find-node').click(function () {
        var val = $('#nodeval').val();

        var found = cy.nodes('node[name="' + val + '"]');
        if (found) {
            found.select();
            cy.zoom({
                level: 10,
                position: found.position()
            });
        }
    });


    $('#find-path').click(function () {
        var from = $('#node-from').val();
        var to = $('#node-to').val();

        $.ajax({
            url: '/breeders_toolbox?shortest-path=1&from=' + from + '&to=' + to,
            type: 'post',
            data: {
                network: $('#cytoscape-data').text()
            },
            success: function (network) {
                network = JSON.parse(network);

                if (!network.nodes.length) {
                    alert("No path found from " + from + " to " + to);
                }
                else {
                    for (var i = 0; i < network.edges.length; i++) {
                        var el = network.edges[i].data;
                        var edge = el.id;
                        var source = el.source;
                        var target = el.target;

                        cy.$('#' + edge + ", #" + source + ", #" + target).select();
                    }
                }
            },
            error: function (e) {
                alert("Error finding shortest path");
            }
        });
    });

    $('.option-list select').change(function () {
        var $this = $(this);

        var name = $this.attr('name');
        var input = $('[name="filter-' + name + '"]');
        input.prop('checked', true);
    });

    
    $('#png-download').click(function () {
        $('#png-data').val(cy.png());
        var tgt = $('#png-data');
        var base64string = tgt.val();
        var imageData = base64string.split(',')[1];
        var a = $("<a>").attr("href", "data:image/jpeg;base64," + imageData )
            .attr("download","image.png")
            .appendTo("body");
        a[0].click();
        a.remove();
    });
    // Graph functions
    $('#compress-btn').click(function () {
        compress(cy);
        cy.fit();
    });

    $('#recenter-btn').click(function () {
        cy.fit();
    });

    $('#disperse-btn').click(function () {
        disperse(cy);
        cy.fit();
    });

    /* Styles */

    $('#node-color').change(function () {
        var color = $(this).val();
        cy.style().selector('node').style('background-color', color).update();
    });

    $('#line-color').change(function () {
        var color = $(this).val();
        cy.style().selector('edge').style('line-color', color).update();
    });

    $('#node-size').change(function () {
        var size = $(this).val();
        cy.style().selector('node').style({
            width: size,
            height: size
        }).update();
    });

    $('#node-shape').change(function () {
        var val = $(this).val();
        cy.style().selector('node').style('shape', val).update();
    });

    $('#line-thickness').change(function () {
        var thickness = $(this).val();
        cy.style().selector('edge').style('width', thickness).update();
    });

    $('#reset-cytoscape').click(function () {
        $('#cyto-layout').val('random').change();
        $('#node-color').val('gray').change();
        $('#line-color').val('gray').change();
        $('#node-size').val('32px').change();
        $('#node-shape').val('ellipse').change();
        $('#line-thickness').val(1).change();
    });

    /*
     * Tabbed forms should be submitted through AJAX and the return content should
     * be used to populate a tab
     */
    /*
      $('form.tabbed').submit(function(e) {
        var $this = $(this);
        var url = $this.attr('action');
        var method = $this.attr('method');
        var data = {
          ajax: true // don't return entire UI
        };

        $this.find('input, select').each(function() {
          var el = $(this);
          var name = el.attr('name');

          if (name) {
            data[name] = el.val();
          }
        });

        console.log("Intercepting action to " + url + " with method " + method);
        for (var prop in data) {
          console.log("    " + prop + ": " + data[prop]);
        }
        $.ajax({
          url: url,
          data: data,
          method: method.toUpperCase(),
          success: function(res) {
            alert(res);
          }
        });
        e.preventDefault();
      });
    */

    $('#short-path-form, #stock-form, #ancestor-form, #filter-form').submit(function () {
        $('.progress').addClass('loading'); // remove this class to stop loading
    });

    /* Examples */

    $('#example1').click(function () { // Illinois Lines
        $('.option-list > li > input').attr('checked', false);
        $('#states').val('Illinois');
        $('#state-checkbox').click();
        $('#filter-submit').click();
    });

    $('#example2').click(function () { // Georgia lines
        $('.option-list > li > input').attr('checked', false);
        $('#states').val('Georgia');
        $('#state-checkbox').click();
        $('#developers').val(50637); // USDA-ARS
        $('#developer-checkbox').click();
        $('#filter-submit').click();
    });

    $('#example3').click(function () { // Shortest path
        $('#node-from').val('B73');
        $('#node-to').val('Mo17');
        $('#shortest-path-submit').click();
    });

    $('#example4').click(function () { // Build network around stock
        $('#data').val('B37');
        $('#data-submit').click();
    });


    $('#example5').click(function () { // Build network around least common ancestor
        $('#node1').val('B41');
        $('#node2').val('W17');
        $('#net-nodes-submit').click();
    });

    $('#example-text1').click(function () {
        $('#input-area').val("parent,child\nchild,child1\nchild,child2\nchild1,child3\nchild2,child4\nchild3,child5\nchild4,child5\n");
    });

    $('#example-text2').click(function () {
        $('#input-area').val("B37");
    });
    
    $('#filter-reset').click(function () {
        console.log("clicked reset button");
        $('#states option:selected').remove();
        $('#developers option:selected').remove();
        $('#sources option:selected').remove();
        $('#countries option:selected').remove();
        
        $('#developer-checkbox').prop('checked', false);
        $('#state-checkbox').prop('checked', false);
        $('#source-checkbox').prop('checked', false);
        $('#country-checkbox').prop('checked', false);
        
    });

});
function embedPedNetStock(stock,element){
    url = "https://zeta.maizegdb.org/breeders_toolbox?data=" + stock;
    console.log(url,element);
    var iframe = document.createElement('iframe');
    iframe.onload = function(){
        var cytoscape_results = this.contentWindow.document.getElementById("cytoscape");
        element.html(cytoscape_results);
    };
    iframe.src = url;
    iframe.width = '0';
    iframe.height = '0';
    document.body.appendChild(iframe);
}

/* Insert a dropdown menu to select an additional state to filter upon */

var num_states = 1;
var max_filter = 10;
function insert_state() {
    var state_html;
    num_states++;
    state_html = "<tr><td>&nbsp;</td><td><select class=\"cyto__input\" id=\"state" + num_states + "\" name=\"state" + num_states + "\">";
    state_html += "<option value=\"\">-</option>";
    $('#state option').each(function() {
        var state = $(this).val();
        if (state != "default") {
            state_html += "<option value=\"" + state + "\">" + state + "</option>";
        }
    });
    state_html += "</select>";
    state_html += "<input type=\"button\" class=\"cyto__button-small\" value=\"Add State\" id=\"btn_add_state\" onclick=\"insert_state(\);this.remove();\"";
    if (num_states == max_filter) {
        console.log("disable button....");
        state_html += " disabled"; //disable the button at 10 items
    }
    state_html += "/>";
    $('#tbl_state tr:last').after(state_html);
    $('#num-states').val(num_states);

}

  $(document).ready(function() {

        $('#states').select2({
          placeholder: 'Select or type a state',
          maximumSelectionLength: 10
        });
        $('#developers').select2({
          placeholder: 'Select or type a developer',
          maximumSelectionLength: 10
        });
        $('#countries').select2({
          placeholder: 'Select or type a country',
          maximumSelectionLength: 10
        });
        $('#sources').select2({
          placeholder: 'Select or type a source',
          maximumSelectionLength: 10
        });
  });
  
      