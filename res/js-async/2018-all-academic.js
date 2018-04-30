(function (w,d) {
    const getSailor = () => {
        // only on sailors pages
        if (!w.location.pathname.startsWith('/sailors/')) {
            return null;
        }

        const data = d.querySelector('meta[name="ts:data"]');
        if (!data) {
            return null;
        }

        const sailor = JSON.parse(data.content);

        // only 2018/2019 sailors are eligible
        if (!['2018', '2019'].includes(sailor.year)) {
            return null;
        }

        return sailor;
    };

    const getSchool = (url) => {
        return new Promise((resolve, reject) => {
            const request = new XMLHttpRequest();
            request.open('GET', url);
            request.responseType = 'document';
            request.onload = () => {
                if (request.status === 200) {
                    resolve(JSON.parse(request.response.documentElement.querySelector('meta[name="ts:data"]').content));
                } else {
                    reject('Unable to get school');
                }
            };
            request.send();
        });
    };

    const generateUrl = (sailor, school) => {
        const machform_base = '';
        const params = [
            `element_2_1=${sailor.first_name}`,
            `element_2_2=${sailor.last_name}`,
            `element_3=${Number(sailor.year) - 2017}`,
            `element_31=${sailor.id}`,
            `element_8=${school.id}`,
        ];
        return `http://colleges.nextmp.net/machform/view.php?id=10244&${params.join('&')}`;
    };

    const createButton = (href) => {
        const c = d.createElement('div');
        c.id = 'nominate-all-american-2018';

        const button = d.createElement('a');
        button.href = href;
        button.innerHTML = '<span class="nominate-small">Nominate for</span> <span class="nominate-large">All-Academic 2018</span>';

        c.appendChild(button);
        d.getElementById('content-header').appendChild(c);
        return button;
    };

    const addStylesheet = () => {
        const s = d.createElement('style');
        s.type = 'text/css';
        s.innerText = `
#content-header {
    position: relative;
}
#nominate-all-american-2018 {
    top: 0em;
    right: 1em;
    position: absolute;
    width: 4em;
    height: 4em;
    background: #E59B54;
    border-radius: 2.5em;
    box-shadow: 1px 1px 2px #555;
    transform: rotate(10deg);
    padding: 0.25em;
}

#nominate-all-american-2018 a {
    color: white;
    text-decoration: none;
}

#nominate-all-american-2018 .nominate-small {
    font-size: 0.5em;
    display: block;
    margin-top: 0.75em;
}

#nominate-all-american-2018 .nominate-large {
    font-size: 0.8em;
    display: block;
    font-weight: bold;
}
`;
        d.head.appendChild(s);
    };

    w.addEventListener('load', (e) => {
        const sailor = getSailor();
        if (sailor) {
            addStylesheet();
            getSchool(sailor.school.substring('url:'.length)).then((school) => {
                createButton(generateUrl(sailor, school));
            }, () => {
                createButton(generateUrl(sailor, { id: '' }));
            });
        }
    }, false);
})(window,document);
