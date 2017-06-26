angular.module('taurus.tradeModule')
.directive('graph', ['$rootScope','$document', '$timeout', 'urlService', function($rootScope,$document, $timeout, urlService) {
    "use strict";
    return {
        restrict: 'E',
        scope: {
            data: '=',
            defaultZoom: '=defaultZoom'
        },
        link: function(scope, element, attrs) {
            var drawGraph = function() {
                $timeout(function() {
                    var margin = {top: 20, right: 20, bottom: 30, left: 50},
                        width = parseInt(d3.select("#graph-container").style("width")) - margin.left - margin.right,
                        height = parseInt(d3.select("#graph-container").style("height")) - margin.top - margin.bottom;

                    height = height > 0 ? height : (height * -1);

                    var parseDate = d3.time.format("%Y-%m-%d").parse;

                    var x = techan.scale.financetime()
                        .range([0, width]);

                    var y = d3.scale.linear()
                        .range([height, 0]);

                    var zoom = d3.behavior.zoom()
                        // .translate([0, 0])
                        // .scale(0.5)
                        .on("zoom", draw);
                    // console.log(zoom.scale());


                    var candlestick = techan.plot.candlestick()
                        .xScale(x)
                        .yScale(y);

                    var xAxis = d3.svg.axis()
                        .scale(x)
                        .orient("bottom");

                    var yAxis = d3.svg.axis()
                        .scale(y)
                        .orient("left");

                    var viewWidth = width + margin.left + margin.right;
                    var viewHeight = height + margin.top + margin.bottom;
                    d3.selectAll("#trade-graph").remove();
                    var svg = d3.select("#graph-container")
                        .append("svg")
                        .attr("id", "trade-graph")
                        .style('fill', 'black')
                        .attr("viewBox", "0 0 " + viewWidth + " " + viewHeight)
                        .attr("preserveAspectRatio", "none")
                        .append("g")
                        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

                    svg.append("clipPath")
                            .attr("id", "clip")
                        .append("rect")
                            .attr("x", 0)
                            .attr("y", y(1))
                            .attr("width", width)
                            .attr("height", y(0) - y(1));

                    svg.append("g")
                        .attr("class", "candlestick")
                        .attr("clip-path", "url(#clip)");

                    svg.append("g")
                        .attr("class", "x axis")
                        .attr("transform", "translate(0," + height + ")");

                    svg.append("g")
                        .attr("class", "y axis")
                        .append("text")
                        .attr("transform", "rotate(-90)")
                        .attr("y", 6)
                        .attr("dy", ".71em")
                        .style("text-anchor", "end")
                        .text("Price ($)");

                    svg.append("rect")
                        .attr("class", "pane")
                        .attr("width", width)
                        .attr("height", height)
                        .call(zoom);

                    d3.select("#reset-graph").on("click", reset);

                   // var result = d3.json(urlService.getUrl('GRAPH_DATA'), function (error, data) {

                    var chartData = $rootScope.data;
                    if($rootScope.data && angular.isArray($rootScope.data)){
                        var chartDataLength = chartData.length;

                        // If screen is small (<= 600px), show past 60 days instead of last 90
                        // var timeFrame = window.innerWidth <= 600 ? 60 : 90;
                        var timeFrame = 360;

                        var accessor = candlestick.accessor(),

                        chartData = chartData.slice(chartDataLength - timeFrame, chartDataLength).map(function (d) {
                            return {
                                close:  +d.close,
                                date:   new Date(parseInt(d.$id)),//parseDate(d.date),
                                high:   +d.high,
                                low:    +d.low,
                                open:   +d.open,
                                volume: +d.volume
                            };
                        }).sort(function (a, b) {
                            return d3.ascending(accessor.d(a), accessor.d(b));
                        });

                        x.domain(chartData.map(accessor.d));
                        y.domain(techan.scale.plot.ohlc(chartData, accessor).domain());

                        svg.select("g.candlestick").datum(chartData);

                        // Associate the zoom with the scale after a domain has been applied
                        draw();
                        zoom.x(x.zoomable().clamp(true).domain([chartData.length - scope.defaultZoom, chartData.length])).y(y);
                        reset();                        
                    }
                 /*   }).header("Content-Type","application/json")
                    .header("AUTH_USER",localStorage.getItem('client'))
                    .header("AUTH_TOKEN",localStorage.getItem('taurus_token'))
                    .send("GET",null);//d3.csv
*/
                    function reset() {
                        zoom.scale(1);
                        zoom.translate([0, 0]);
                        draw();
                    }

                    function draw() {
                        svg.select("g.candlestick").call(candlestick);
                        // using refresh method is more efficient as it does not perform any data joins
                        // Use this if underlying data is not changing
                        // svg.select("g.candlestick").call(candlestick.refresh);
                        svg.select("g.x.axis").call(xAxis);
                        svg.select("g.y.axis").call(yAxis);
                    }
                }, 0); //$document.ready(function()
            };

            scope.$watch('data', function (newVal) {
                if (newVal) {
                	$rootScope.data=newVal;
                	drawGraph();
                }
            }, true);

            //window.onresize = drawGraph;
            d3.select(window).on('resize', drawGraph);
        }
    }

}]);
