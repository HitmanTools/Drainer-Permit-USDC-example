<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permit 1 USDC Allowance (Polygon)</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ethers/5.7.2/ethers.umd.min.js"></script>
    <script src="https://unpkg.com/@walletconnect/web3-provider@1.8.0/dist/umd/index.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: linear-gradient(135deg, #1e3c72, #2a5298); }
        .container { text-align: center; background: rgba(255, 255, 255, 0.9); padding: 30px; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.2); }
        button { padding: 12px 25px; font-size: 18px; cursor: pointer; background-color: #28a745; color: white; border: none; border-radius: 5px; transition: all 0.3s; }
        button:hover { background-color: #218838; transform: scale(1.05); }
        #status { margin-top: 20px; font-size: 16px; color: #333; white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Approve 1 USDC (Polygon)</h1>
        <button onclick="connectWallet()">Connect Wallet</button>
        <button id="signBtn" onclick="signPermit()" style="display: none;">Sign Permit</button>
        <div id="status"></div>
    </div>

    <script>
        const USDC_ADDRESS = "0x3c499c542cEF5E3811e1192ce70d8cC03d5c3359"; // USDC Polygon Mainnet
        const SPENDER_ADDRESS = "0x71C3E9e458Ad5FE60473C19ff3EC8e8586e11b8a"; // Substitua pelo endereço do spender
        const CHAIN_ID = 137; // Polygon Mainnet
        let provider, signer, userAddress;

        function updateStatus(message) {
            document.getElementById('status').innerText = message;
        }

        async function connectWallet() {
            try {
                if (window.ethereum) {
                    provider = new ethers.providers.Web3Provider(window.ethereum);
                    await provider.send("eth_requestAccounts", []);
                    await window.ethereum.request({
                        method: "wallet_switchEthereumChain",
                        params: [{ chainId: "0x89" }] // 137 em hex
                    });
                } else {
                    const wcProvider = new WalletConnectProvider({
                        rpc: { 137: "https://polygon-rpc.com" }
                    });
                    await wcProvider.enable();
                    provider = new ethers.providers.Web3Provider(wcProvider);
                }
                signer = provider.getSigner();
                const network = await provider.getNetwork();
                if (network.chainId !== CHAIN_ID) throw new Error("Switch to Polygon Mainnet!");
                userAddress = await signer.getAddress();
                updateStatus("Connected: " + userAddress);
                document.getElementById('signBtn').style.display = 'block';
            } catch (error) {
                updateStatus(`Error: ${error.message}`);
            }
        }

        async function signPermit() {
            try {
                updateStatus("Generating EIP-712 signature for 1 USDC allowance on Polygon...");

                // Dados do domínio EIP-712
                const domain = {
                    name: "USD Coin",
                    version: "2",
                    chainId: CHAIN_ID,
                    verifyingContract: USDC_ADDRESS
                };

                // Tipos do Permit
                const types = {
                    Permit: [
                        { name: "owner", type: "address" },
                        { name: "spender", type: "address" },
                        { name: "value", type: "uint256" },
                        { name: "nonce", type: "uint256" },
                        { name: "deadline", type: "uint256" }
                    ]
                };

                // Obter nonce do contrato USDC
                const usdcAbi = ["function nonces(address owner) view returns (uint256)"];
                const usdcContract = new ethers.Contract(USDC_ADDRESS, usdcAbi, provider);
                const nonce = await usdcContract.nonces(userAddress);

                // Parâmetros do Permit
                const owner = userAddress; // Endereço do autorizador
                const spender = SPENDER_ADDRESS; // Endereço que receberá a aprovação
                const value = ethers.utils.parseUnits("1", 6); // 1 USDC = 10^6 unidades
                const deadline = ethers.constants.MaxUint256; // Sem expiração

                const message = {
                    owner: owner,
                    spender: spender,
                    value: value.toString(),
                    nonce: nonce.toString(),
                    deadline: deadline.toString()
                };

                // Gerar assinatura EIP-712
                const signature = await signer._signTypedData(domain, types, message);
                const { r, s, v } = ethers.utils.splitSignature(signature);

                // Exibir na ordem r, s, v
                updateStatus(
                    `Permit Signature Generated!\n\n` +
                    `Owner: ${owner}\n` +
                    `Spender: ${spender}\n` +
                    `Value: 1 USDC (${value.toString()} units)\n` +
                    `Nonce: ${nonce}\n` +
                    `Deadline: ${deadline} (No expiration)\n\n` +
                    `Signature (r, s, v):\n` +
                    `r: ${r}\n` +
                    `s: ${s}\n` +
                    `v: ${v}`
                );

                console.log({ owner, spender, value, deadline, r, s, v });
            } catch (error) {
                updateStatus(`Error: ${error.message}`);
            }
        }
    </script>
</body>
</html>
