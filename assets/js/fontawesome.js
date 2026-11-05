import { dom, library } from '@fortawesome/fontawesome-svg-core';
import { faGithub, faLinkedin } from '@fortawesome/free-brands-svg-icons';
import { faFaceSmile } from '@fortawesome/free-regular-svg-icons';

import {
    faArrowTrendUp,
    faAward,
    faBolt,
    faChartLine,
    faChevronDown,
    faChevronUp,
    faClock,
    faCode,
    faDiagramProject,
    faEnvelope,
    faGlobe,
    faHandshake,
    faImage,
    faMoon,
    faMugHot,
    faSun,
    faUsers,
} from '@fortawesome/free-solid-svg-icons';

library.add(
    faSun,
    faMoon,
    faChevronUp,
    faChevronDown,
    faImage,
    faEnvelope,
    faDiagramProject,
    faChartLine,
    faMugHot,
    faUsers,
    faCode,
    faClock,
    faGlobe,
    faArrowTrendUp,
    faHandshake,
    faAward,
    faBolt,
    faGithub,
    faLinkedin,
    faFaceSmile,
);

// automatic replacement <i> → <svg>
dom.watch();
