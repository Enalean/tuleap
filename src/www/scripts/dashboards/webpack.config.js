/* eslint-disable */
var path = require('path');
module.exports = {
    entry: path.resolve(__dirname, 'dashboard.js'),
    output: {
        path: path.resolve(__dirname, '../../assets'),
        filename: 'dashboard.min.js'
    },
    resolve: {
        modules: [ 'node_modules' ]
    },
    externals: {
        jquery: 'jQuery',
        tlp   : 'tlp'
    }
};
