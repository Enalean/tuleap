/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
var tuleap = tuleap || {};
tuleap.barChart = {};

tuleap.barChart.margin = {
    top: 20,
    right: 20,
    bottom: 30,
    left: 40
};
tuleap.barChart.width  = 960 - tuleap.barChart.margin.left - tuleap.barChart.margin.right;
tuleap.barChart.height = 500 - tuleap.barChart.margin.top - tuleap.barChart.margin.bottom;
tuleap.barChart.x0     = d3.scale.ordinal()
    .rangeRoundBands( [ 0, tuleap.barChart.width ], .1 );
tuleap.barChart.x1     = d3.scale.ordinal();
tuleap.barChart.y      = d3.scale.linear()
    .range( [ tuleap.barChart.height, 0 ] );
tuleap.barChart.color  = d3.scale.ordinal()
    .range( [ "#f09516", "#fee966" ] );
tuleap.barChart.xAxis  = d3.svg.axis()
    .scale( tuleap.barChart.x0 )
    .orient( "bottom" );
tuleap.barChart.yAxis   = d3.svg.axis()
    .scale( tuleap.barChart.y )
    .orient( "left" )
    .tickFormat( d3.format( ".2s" ) );
tuleap.barChart.svg     = d3.select( ".chart_container" ).append( "svg" )
    .attr( "width", tuleap.barChart.width + tuleap.barChart.margin.left + tuleap.barChart.margin.right )
    .attr( "height", tuleap.barChart.height + tuleap.barChart.margin.top + tuleap.barChart.margin.bottom )
    .append( "g" )
    .attr( "transform", "translate(" + tuleap.barChart.margin.left + "," + tuleap.barChart.margin.top + ")" );

tuleap.barChart.fillColumns = function(datas){
    var column_names = d3.keys( datas[0] ).filter( function( key ){
        return key !== "week";
    });

    datas.forEach( function( data ){
        data.quartiles = column_names.map( function( name ){
            return {
                name: name,
                value: +data[name]
            };
        });
    } );
}

d3.csv( "index.php?action=provide_datas", function( error, datas ){
    var bar_chart = tuleap.barChart,
        quartiles,
        week,
        legend;

    quartiles = d3.keys( datas[0] ).filter( function( key ){
        return key !== "week";
    });

    bar_chart.fillColumns(datas, quartiles);

    bar_chart.x0.domain( datas.map( function( data ){
        return data.week;
    }));

    bar_chart.x1.domain( quartiles ).rangeRoundBands( [ 0, bar_chart.x0.rangeBand() ] );
    bar_chart.y.domain(
        [
            0,
            d3.max( datas, function( data ){
                return d3.max( data.quartiles, function( data ){
                    return data.value;
                });
            })
        ]);

    bar_chart.svg.append( "g" )
        .attr( "class", "x axis" )
        .attr( "transform", "translate(0," + bar_chart.height + ")" )
        .call( bar_chart.xAxis )
        .selectAll( "text" )
        .style( "text-anchor", "end" )
        .attr( "dx", "-.8em" )
        .attr( "dy", ".15em" )
        .attr( "transform", function( d ){
            return "rotate(-65)";
        });

    bar_chart.svg.append( "g" )
        .attr( "class", "y axis" )
        .call( bar_chart.yAxis );

    week = bar_chart.svg.selectAll( ".week" )
        .data( datas )
        .enter().append( "g" )
        .attr( "class", "g" )
        .attr( "transform", function( data ){
            return "translate(" + bar_chart.x0( data.week ) + ",0)";
        });

    week.selectAll( "rect" )
        .data( function( data ){
            return data.quartiles;
        })
        .enter().append( "rect" )
        .attr( "width", bar_chart.x1.rangeBand() )
        .attr( "x", function( data ){
            return bar_chart.x1( data.name );
        })
        .attr( "y", function( data ){
            return bar_chart.y( data.value );
        })
        .attr( "height", function( data ){
            return bar_chart.height - bar_chart.y( data.value );
        })
        .style( "fill", function( data ){
            return bar_chart.color( data.name );
        });

    legend = bar_chart.svg.selectAll( ".legend" )
        .data( quartiles.slice().reverse() )
        .enter().append( "g" )
        .attr( "class", "legend" )
        .attr( "transform", function( data, index ){
            return "translate(0," + index * 20 + ")";
        });

    legend.append( "rect" )
        .attr( "x", bar_chart.width - 18 )
        .attr( "width", 18 )
        .attr( "height", 18 )
        .style( "fill", bar_chart.color );

    legend.append( "text" )
        .attr( "x", bar_chart.width - 24 )
        .attr( "y", 9 )
        .attr( "dy", ".35em" )
        .style( "text-anchor", "end" )
        .text( function( data ){
            return data;
        });
} );