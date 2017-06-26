<div class="changeGraphType">
    <ul>
        <li class="candle-graph active"><a id="candleGraph" onclick="changeTypeGraph(0); return false;"></a></li>
        <li class="line-graph"><a id="lineGraph" onclick="changeTypeGraph(1); return false;"></a></li>
    </ul>
</div>
<div id="graph"></div>

<script src="<?=asset('amcharts/amcharts.js') ?>" type="text/javascript"></script>
<script src="<?=asset('amcharts/serial.js') ?>" type="text/javascript"></script>
<script src="<?=asset('amcharts/amstock.js') ?>" type="text/javascript"></script>

<script type="text/javascript">
    var chart;
    var graph1=null;
    var graph2=null;
    var stockPanel1 = null;
    var stockLegend2 = null;
    
    AmCharts.ready(function () {
        initCharts();
    });
    
    function initCharts() {
        chart = new AmCharts.AmStockChart();
        AmCharts.useUTC = true;
        chart.pathToImages = "<?=asset('amcharts/images/')?>";
        // As we have minutely data, we should set minPeriod to "mm"
        var categoryAxesSettings = new AmCharts.CategoryAxesSettings();
        categoryAxesSettings.minPeriod = "DD";

        chart.categoryAxesSettings = categoryAxesSettings;

        // DATASETS //////////////////////////////////////////
        var dataSet = new AmCharts.DataSet();
        dataSet.color = "#85B068";
        dataSet.fieldMappings = [{
            fromField: "value",
            toField: "value"
        }, {
            fromField: "volume",
            toField: "volume"
        }, {
            fromField: "open",
            toField: "open"
        }, {
            fromField: "close",
            toField: "close"
        }, {
            fromField: "high",
            toField: "high"
        }, {
            fromField: "low",
            toField: "low"
        }];

        dataSet.dataProvider = chartData;

        dataSet.categoryField = "date";

        // set data sets to the chart
        chart.dataSets = [dataSet];

        // PANELS ///////////////////////////////////////////

        // first stock panel
        stockPanel1 = new AmCharts.StockPanel();
        stockPanel1.showCategoryAxis = true;
        //stockPanel1.title = "Value";
        stockPanel1.percentHeight = 100;
        stockPanel1.backgroundColor = "#faf7f7";
        stockPanel1.backgroundAlpha = 1;
        stockPanel1.fontFamily = "Arial, Sans-Serif";
        stockPanel1.plotAreaFillColors = "#fff";
        stockPanel1.plotAreaFillAlphas = "1";

        // normal mode chart
        graph1 = new AmCharts.StockGraph();
        graph1.valueField = "value";
        graph1.type = "smoothedLine";
        graph1.lineThickness = 2;
        graph1.bullet = "round";
        graph1.bulletBorderColor = "#fff";
        graph1.bulletBorderAlpha = 0.8;
        graph1.bulletBorderThickness = 1;
        graph1.linecolor = "#85B068";

        // candle mode
        graph2 = new AmCharts.StockGraph();
        graph2.type = "candlestick";
        graph2.proCandlesticks = true;
        graph2.lineThickness = 1;
        graph2.openField = "open";
        graph2.highField = "high";
        graph2.lowField = "low";
        graph2.closeField = "close";
        graph2.balloonText = "Open:<b>[[open]]</b><br>Low:<b>[[low]]</b><br>High:<b>[[high]]</b><br>Close:<b>[[close]]</b><br>";
        graph2.fillColors = "#D8F1DA";
        graph2.lineColor = "#80D187";
        graph2.negativeFillColors = "#F4E2CF";
        graph2.negativeLineColor = "#F3A95A";
        graph2.fillAlphas = 0.5;
        graph2.lineAlpha = 1;
        graph2.title = 'Price';

        stockPanel1.addStockGraph(graph2);

        // create stock legend
        var stockLegend1 = new AmCharts.StockLegend();
        stockLegend1.valueTextRegular = " ";
        stockLegend1.markerType = "none";
        stockLegend1.backgroundColor = "#F0F2F2";
        stockLegend1.backgroundAlpha = 1;
        //stockPanel1.stockLegend = stockLegend1;

        // second stock panel
        stockPanel2 = new AmCharts.StockPanel();
        stockPanel2.title = "Volume";
        stockPanel2.percentHeight = 30;
        stockPanel2.showCategoryAxis = false;

        stockPanel2.backgroundColor = "#f00";
        stockPanel2.backgroundAlpha = 1;
        stockPanel2.fontFamily = "Arial, Sans-Serif";
        stockPanel2.fontSize = "0.9em";
        stockPanel2.plotAreaFillColors = "#fff";
        stockPanel2.plotAreaFillAlphas = "1";

        var volume = new AmCharts.StockGraph();
        volume.valueField = "volume";
        volume.type = "column";
        volume.cornerRadiusTop = 0;
        volume.fillAlphas = 0.7;
        volume.lineAlpha = 0;
        volume.fillColors = "#D8F1DA";
        stockPanel2.addStockGraph(volume);

        // create stock legend
        var stockLegend2 = new AmCharts.StockLegend();
        stockLegend2.valueTextRegular = " ";
        stockLegend2.markerType = "none";
        stockLegend2.backgroundColor = "#F0F2F2";
        stockLegend2.backgroundAlpha = 1;
        //stockPanel2.stockLegend = stockLegend2;

        // set panels to the chart
        chart.panels = [stockPanel1, stockPanel2];

        // OTHER SETTINGS ////////////////////////////////////
        var scrollbarSettings = new AmCharts.ChartScrollbarSettings();
        scrollbarSettings.graph = graph1;
        scrollbarSettings.usePeriod = "WW"; // this will improve performance
        scrollbarSettings.position = "bottom";
        chart.chartScrollbarSettings = scrollbarSettings;

        var cursorSettings = new AmCharts.ChartCursorSettings();
        cursorSettings.valueBalloonsEnabled = true;
        cursorSettings.categoryBalloonColor = "#5C4B51";
        cursorSettings.cursorColor = "#5C4B51";
        cursorSettings.valueLineEnabled = true;
        chart.chartCursorSettings = cursorSettings;

        // PERIOD SELECTOR ///////////////////////////////////
        var periodSelector = new AmCharts.PeriodSelector();
        periodSelector.position = "top";
        periodSelector.dateFormat = "YYYY-MM-DD";
        periodSelector.inputFieldWidth = 100;

        periodSelector.periods = [
            //{period:"DD", count:1, label:"1 day"},
            {period: "DD", count: 7, label: "   7d   "},
            {period: "MM", count: 1, label: "   1m   "},
            {period: "MM", selected: true, count: 3, label: "   3m   "},
            {period: "MM", count: 6, label: "   6m   "},
            {period: "YTD", label: "   YTD   "},
            {period: "YYYY", count: 1, label: "   1yr   "},
            {period: "MAX", label: "   All   "}
        ];

        chart.periodSelector = periodSelector;

        var panelsSettings = new AmCharts.PanelsSettings();
        panelsSettings.usePrefixes = true;
        chart.panelsSettings = panelsSettings;

        // no x grid
        chart.categoryAxesSettings.gridThickness = 0;

        // let's add a listener to remove the loading indicator when the chart is
        // done loading
        chart.addListener("rendered", function (event) {
            $("chartloading").hide();
        });
        
        chart.write('graph');
    }

    
    function changeTypeGraph(type) {
        if(type == 0) {
            $('.candle-graph').addClass('active');
            $('.line-graph').removeClass('active');
            (chart.panels[0]).removeStockGraph(graph1);
            (chart.panels[0]).addStockGraph(graph2);
        } else {
            $('.line-graph').addClass('active');
            $('.candle-graph').removeClass('active');
            (chart.panels[0]).removeStockGraph(graph2);
            (chart.panels[0]).addStockGraph(graph1);
        }
        
        chart.validateNow();
    }

    var chartData = <?=$chartdata?>;
</script>