export default FilepathsService;

FilepathsService.$inject = [];

function FilepathsService() {
    const self = this;
    let filepaths = [];

    Object.assign(self, {
        setFilepaths,
        previous,
        next,
    });

    function setFilepaths(files) {
        filepaths = files.map(({ path }) => path);
    }

    function previous(filepath) {
        const index = filepaths.indexOf(filepath);
        return index > 0 ? filepaths[index - 1] : "";
    }

    function next(filepath) {
        const index = filepaths.indexOf(filepath);
        return index < filepaths.length - 1 ? filepaths[index + 1] : "";
    }
}
