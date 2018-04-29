(function (w,d) {
    const getSchool = () => {
        // only on sailors pages
        if (!w.location.pathname.startsWith('/schools/')) {
            return null;
        }
        const data = d.querySelector('meta[name="ts:data"]');
        if (!data) {
            return null;
        }
        return JSON.parse(data.content);
    };

    const addSchoolCode = (school) => {
        const c = d.createElement('li');
        c.innerHTML = `<span class="page-info-key">School code</span><span class="page-info-value">${school.id}</span>`;

        const i = d.getElementById('page-info');
        i.insertBefore(c, i.childNodes[1]);
    };

    w.addEventListener('load', (e) => {
        const school = getSchool();
        if (school) {
            addSchoolCode(school);
        }
    }, false);
})(window,document);
