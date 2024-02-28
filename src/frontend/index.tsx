import React, { useContext, useEffect, useState } from 'react';
import ForgeReconciler, { Code, Image, Text, useProductContext } from '@forge/react';
import { invokeRemote, InvokeRemoteInput, requestJira } from '@forge/bridge';
import { getContext } from '@forge/bridge/out/view/getContext';
import api, { route } from '@forge/api';

function App() {
    const [ qrCode, setQrCode ] = useState<string>('');
    const [ runtime, setRuntime ] = useState<number>(0);
    const [ loading, setLoading ] = useState(false);

    useEffect(() => {
        setLoading(true);

        const fetch = async () => {
            const issueKey = (await getContext()).extension.issue.key;
            const issue = await (await requestJira(`/rest/api/2/issue/${issueKey}?fields=summary,description`)).json();
            console.log(issue);

            const result = await invokeRemote<{ body: { qrCode: string, runtime: number } }>({
                method: 'POST',
                path: '',
                body: {
                    'summary': issue.fields.summary,
                    'description': issue.fields.description,
                },
            });

            setQrCode(result.body.qrCode);
            setRuntime(Math.floor(result.body.runtime * 1000));

            setLoading(false);
        };

        fetch();
    }, []);

    if (loading) {
        return (
            <Text>Loading ...</Text>
        );
    }

    return (
        <>
            <Image src={ qrCode } size="medium"/>
            <Text><Code>{ `Generated in ${ runtime } ms` }</Code></Text>
        </>
    );
};

ForgeReconciler.render(
    <React.StrictMode>
        <App/>
    </React.StrictMode>,
);
