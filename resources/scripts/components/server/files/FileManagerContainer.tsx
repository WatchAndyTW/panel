import React, { useEffect, useState } from 'react';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ServerContext } from '@/state/server';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import { CSSTransition } from 'react-transition-group';
import Spinner from '@/components/elements/Spinner';
import FileObjectRow from '@/components/server/files/FileObjectRow';
import FileManagerBreadcrumbs from '@/components/server/files/FileManagerBreadcrumbs';

export default () => {
    const [ loading, setLoading ] = useState(true);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const { contents: files, directory } = ServerContext.useStoreState(state => state.files);
    const { getDirectoryContents } = ServerContext.useStoreActions(actions => actions.files);

    useEffect(() => {
        setLoading(true);
        clearFlashes();

        getDirectoryContents(window.location.hash.replace(/^#(\/)*/, '/'))
            .then(() => setLoading(false))
            .catch(error => {
                console.error(error.message, { error });
                addError({ message: httpErrorToHuman(error), key: 'files' });
            });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [ directory ]);

    return (
        <div className={'my-10 mb-6'}>
            <FlashMessageRender byKey={'files'} className={'mb-4'}/>
            <React.Fragment>
                <FileManagerBreadcrumbs/>
                {
                    loading ?
                        <Spinner size={'large'} centered={true}/>
                        :
                        !files.length ?
                            <p className={'text-sm text-neutral-600 text-center'}>
                                This directory seems to be empty.
                            </p>
                            :
                            <CSSTransition classNames={'fade'} timeout={250} appear={true} in={true}>
                                <div>
                                    {files.length > 250 ?
                                        <React.Fragment>
                                            <div className={'rounded bg-yellow-400 mb-px p-3'}>
                                                <p className={'text-yellow-900 text-sm text-center'}>
                                                    This directory is too large to display in the browser, limiting
                                                    the output to the first 250 files.
                                                </p>
                                            </div>
                                            {
                                                files.slice(0, 500).map(file => (
                                                    <FileObjectRow key={file.uuid} file={file}/>
                                                ))
                                            }
                                        </React.Fragment>
                                        :
                                        files.map(file => (
                                            <FileObjectRow key={file.uuid} file={file}/>
                                        ))
                                    }
                                </div>
                            </CSSTransition>
                }
            </React.Fragment>
        </div>
    );
};
